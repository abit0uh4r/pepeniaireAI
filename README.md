# Pépinière IA

Application monolithique Laravel 13 pour la gestion d’une pépinière et le parcours de conseil assisté par IA.

Le projet fournit un catalogue de plantes administré par un gérant authentifié et un formulaire public de conseil : Breeze Blade, Tailwind CSS, Alpine.js, Pest, MySQL 8.4, queue database, Docker Compose et GitHub Actions.

## Prérequis

Installation Docker recommandée :

- Docker Desktop avec Docker Compose v2 ;
- Git.

Installation sans Docker :

- PHP 8.3 avec PDO MySQL, mbstring, intl, bcmath, pcntl et zip ;
- Composer 2 ;
- Node.js 22 et npm ;
- MySQL 8.4.

## Configuration

PowerShell :

```powershell
Copy-Item .env.example .env
composer install
npm ci
php artisan key:generate
```

Bash :

```bash
cp .env.example .env
composer install
npm ci
php artisan key:generate
```

Renseignez ensuite ces valeurs dans `.env` :

```dotenv
DB_PASSWORD=mot-de-passe-local
DB_ROOT_PASSWORD=mot-de-passe-root-local
MANAGER_NAME="Nom du gérant"
MANAGER_EMAIL=manager@example.test
MANAGER_PASSWORD=mot-de-passe-de-12-caracteres-minimum
```

`.env.example` ne contient aucun secret. Ne versionnez jamais `.env`.

## Installation avec Docker Compose

Construisez puis démarrez les services :

```bash
docker compose config
docker compose build
docker compose up -d
docker compose ps
```

Créez les tables et le compte gérant :

```bash
docker compose exec app php artisan migrate
docker compose exec app php artisan db:seed
```

Pour repartir d’une base vide, la commande suivante supprime toutes les tables avant de migrer et de lancer les seeders :

```bash
docker compose exec app php artisan migrate:fresh --seed
```

Accès locaux :

- application : <http://localhost:8088>
- santé : <http://localhost:8088/health>
- connexion gérant : <http://localhost:8088/login>
- MySQL depuis l’hôte : `127.0.0.1:33060`

Le conteneur `queue-worker` utilise la même image `pepiniereia-app:local` que le conteneur `app`.

## Installation sans Docker

Adaptez `.env` pour les services qui tournent sur l’hôte :

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
MAIL_MAILER=log
```

Installez et initialisez le projet :

```bash
composer install
npm ci
php artisan key:generate
php artisan migrate
php artisan db:seed
npm run build
```

Démarrez le serveur, Vite et le worker dans des terminaux distincts :

```bash
php artisan serve
npm run dev
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

## Compte gérant

La commande interactive `manager:create` provisionne ou met à jour le compte gérant. Le compte est défini par :

- `MANAGER_NAME` ;
- `MANAGER_EMAIL` ;
- `MANAGER_PASSWORD`.

Le mot de passe doit contenir au moins 12 caractères. La commande vérifie l’adresse email, la confirmation du mot de passe et marque le compte comme vérifié. L’inscription publique est désactivée : les routes `GET /register` et `POST /register` répondent avec une erreur 404.

Créez le compte interactif :

```bash
php artisan manager:create
```

Avec Docker :

```bash
docker compose exec app php artisan manager:create
```

Pour les migrations et les tests, `DatabaseSeeder` conserve `ManagerSeeder` afin de provisionner automatiquement le compte configuré.

Relancez le seeder après une modification des identifiants :

```bash
php artisan db:seed --class=ManagerSeeder
```

Avec Docker :

```bash
docker compose exec app php artisan db:seed --class=ManagerSeeder
```

## Catalogue et stock

Le catalogue est accessible uniquement au gérant connecté et vérifié depuis `/admin/plants`.

Le formulaire permet de renseigner le nom, l’espèce, l’environnement, une exposition, les niveaux d’arrosage et d’entretien, les dimensions adultes, le prix et le stock. Les fiches peuvent être recherchées, filtrées, modifiées, désactivées ou archivées par suppression logique.

Pour initialiser le catalogue de démonstration (23 plantes variées) :

```bash
php artisan db:seed --class=PlantSeeder
```

Les plantes inactives ou en rupture restent dans la base mais ne seront pas candidates au futur préfiltrage métier.

## Demande de conseil publique

Le formulaire public est disponible sur `/conseil`, sans création de compte. Il collecte l’environnement, l’exposition, la taille de l’espace, l’entretien disponible et une description libre. Le prénom et le numéro de téléphone du visiteur sont facultatifs ; aucune adresse email n’est demandée. Le téléphone est visible uniquement par le gérant dans l’administration et n’est jamais transmis à Groq.

Une soumission valide crée une demande au statut `PENDING` et un token public aléatoire de 64 caractères hexadécimaux, puis redirige vers `/conseil/suivi/{token}`. Cette page affiche l’état courant et interroge le point `/conseil/suivi/{token}/status` avec un polling limité, sans exposer les données personnelles de la demande. Le traitement asynchrone appelle Groq via le SDK `laravel/ai`.

Lorsque le traitement est terminé, la page présente le résumé, les recommandations validées, les raisons, les informations d’entretien et la quantité observée. Le gérant retrouve l’historique simple dans `/admin/advice-requests`.

## Parcours de démonstration

La démonstration utilise Groq et nécessite une clé configurée dans le fichier `.env` local :

```bash
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed
docker compose ps
```

1. Ouvrir `http://localhost:8088/admin` et vérifier les plantes de démonstration.
2. Ouvrir `http://localhost:8088/conseil` dans une fenêtre privée.
3. Soumettre une demande compatible avec le catalogue.
4. Conserver la page de suivi ouverte : le worker traite automatiquement la demande.
5. Consulter le résultat public, puis son audit dans **Administration → Demandes**.

Pour observer le traitement :

```bash
docker compose logs --tail=50 queue-worker
```

## Queue

La configuration utilise `QUEUE_CONNECTION=database`. La migration technique crée `jobs` et `failed_jobs`.

Les nouvelles demandes sont placées dans la queue par `GeneratePlantAdviceJob`. `GroqPlantAdvisor` utilise un Agent du SDK officiel `laravel/ai` avec une sortie structurée et ne reçoit que les candidates préfiltrées par Laravel. `PlantEligibilityService` applique les contraintes d’activité, de stock, d’environnement, d’exposition, d’espace et d’entretien. Le Job revalide directement la structure, les identifiants, les doublons et la limite avant la persistance transactionnelle des recommandations et du snapshot de quantité.

Vérifiez le worker Docker :

```bash
docker compose ps queue-worker
docker compose logs --tail=50 queue-worker
```

Lancez un worker ponctuel :

```bash
docker compose exec app php artisan queue:work --once
```

## Fournisseur Groq

Groq est le fournisseur unique de l’application. Fournissez la clé uniquement dans l’environnement local :

```bash
GROQ_API_KEY=... php artisan queue:work --once
```

Les variables `GROQ_MODEL`, `GROQ_BASE_URL`, `GROQ_TIMEOUT` et `GROQ_MAX_TOKENS` sont documentées dans `.env.example`, tandis que `config/ai.php` configure le fournisseur Groq du SDK. L’Agent demande à Groq de rédiger exclusivement en français les résumés, conseils et raisons. Les réponses déjà enregistrées ne sont pas retraduites. La CI et les tests utilisent `PlantAdviceAgent::fake()` et n’appellent jamais le réseau Groq.

Les réponses publiques de suivi utilisent des tokens aléatoires et sont envoyées avec `Cache-Control: private, no-store`. L’application ajoute également des en-têtes de sécurité communs sur ses réponses HTTP.

## Emails locaux

Les emails locaux utilisent le driver `log`. Aucun serveur SMTP n’est requis pour démarrer le projet.

## Tests et qualité

Suite complète :

```bash
php artisan test
vendor/bin/pint --test
npm run build
composer validate --strict
```

Dans Docker :

```bash
docker compose exec \
  -e APP_ENV=testing \
  -e SESSION_DRIVER=array \
  -e CACHE_STORE=array \
  -e DB_CONNECTION=sqlite \
  -e DB_DATABASE=:memory: \
  -e QUEUE_CONNECTION=sync \
  -e MAIL_MAILER=array \
  app php artisan test
docker compose exec app vendor/bin/pint --test
```

Tests ciblés :

```bash
php artisan test --filter=Authentication
php artisan test --filter=ManagerSeeder
php artisan test --filter=HealthCheck
```

## Migrations

Migration normale :

```bash
php artisan migrate
```

Réinitialisation complète, destructive :

```bash
php artisan migrate:fresh --seed
```

État des migrations :

```bash
php artisan migrate:status
```

## Commandes Docker utiles

```bash
docker compose up -d
docker compose stop
docker compose logs -f app
docker compose logs -f queue-worker
docker compose exec app php artisan about
docker compose exec app php artisan route:list
```

La commande suivante supprime les conteneurs et les volumes MySQL et stockage Laravel. Elle efface les données locales :

```bash
docker compose down --volumes
```

## Architecture de la phase 1

```text
Navigateur
    |
  Nginx :8088
    |
Laravel PHP-FPM 8.3
    |-- MySQL 8.4
    `-- jobs database <- queue-worker
```

Les sessions et le cache utilisent des fichiers. Redis, Horizon, Sanctum, React, Vue, Livewire et Inertia ne font pas partie du projet.

## Documentation

- [Conventions du projet](AGENTS.md)
- [Décisions techniques](docs/technical-decisions.md)
- [Plan d’implémentation](docs/implementation-plan.md)
- `docs/Cahier_des_Charges_Pepiniere_IA.docx`
- `docs/architecture-pepiniere-ia.png`
