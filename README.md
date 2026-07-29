# Pépinière IA

Application monolithique Laravel 13 pour la gestion d’une pépinière et le futur parcours de conseil assisté par IA.

Le projet fournit un catalogue de plantes administré par un gérant authentifié et un formulaire public de conseil : Breeze Blade, Tailwind CSS, Alpine.js, Pest, MySQL 8.4, queue database, Mailpit, Docker Compose et GitHub Actions.

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
MANAGER_EMAIL=gerant@example.test
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
- Mailpit : <http://localhost:8025>
- MySQL depuis l’hôte : `127.0.0.1:33060`

Le conteneur `queue-worker` utilise la même image `pepiniereia-app:local` que le conteneur `app`.

## Installation sans Docker

Adaptez `.env` pour les services qui tournent sur l’hôte :

```dotenv
DB_HOST=127.0.0.1
DB_PORT=3306
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
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

Le formulaire permet de renseigner le nom, l’espèce, l’environnement, les expositions, les niveaux d’arrosage et d’entretien, les dimensions adultes, la sécurité animale, le prix et le stock. Les fiches peuvent être recherchées, filtrées, modifiées, désactivées ou archivées par suppression logique.

Pour initialiser les trois plantes de démonstration :

```bash
php artisan db:seed --class=PlantSeeder
```

Les plantes inactives ou en rupture restent dans la base mais ne seront pas candidates au futur préfiltrage métier.

## Demande de conseil publique

Le formulaire public est disponible sur `/conseil`, sans création de compte. Il collecte l’environnement, l’exposition, la taille de l’espace, l’entretien disponible, la présence d’animaux et une description libre. Le nom et l’email sont facultatifs.

Une soumission valide crée une demande au statut `PENDING` et un token public aléatoire de 64 caractères hexadécimaux, puis redirige vers `/conseil/suivi/{token}`. Cette page affiche l’état courant et interroge le point `/conseil/suivi/{token}/status` avec un polling limité, sans exposer les données personnelles de la demande. Le traitement asynchrone et l’IA restent reportés aux phases suivantes.

## Queue

La configuration utilise `QUEUE_CONNECTION=database`. La migration technique crée `jobs` et `failed_jobs`.

Les nouvelles demandes sont placées dans la queue par `GeneratePlantAdviceJob`. Le fournisseur par défaut est `FakePlantAdvisor` (`AI_PROVIDER=fake`) : il est déterministe, n’appelle aucun réseau et ne reçoit que les candidates préfiltrées par Laravel. `PlantEligibilityService` applique les contraintes d’activité, de stock, d’environnement, d’exposition, d’espace, d’entretien et de sécurité animale. La validation et la persistance des recommandations restent prévues à la phase 8.

Vérifiez le worker Docker :

```bash
docker compose ps queue-worker
docker compose logs --tail=50 queue-worker
```

Lancez un worker ponctuel :

```bash
docker compose exec app php artisan queue:work --once
```

## Mailpit

Laravel envoie les emails locaux vers `mailpit:1025`. L’interface web écoute sur le port `8025`.

PowerShell :

```powershell
Invoke-WebRequest http://localhost:8025 -UseBasicParsing
```

Bash :

```bash
curl --fail http://localhost:8025
```

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

La commande suivante supprime les conteneurs et les volumes MySQL, Mailpit et stockage Laravel. Elle efface les données locales :

```bash
docker compose down --volumes
```

## Architecture de la phase 1

```text
Navigateur
    |
  Nginx :8080
    |
Laravel PHP-FPM 8.3
    |-- MySQL 8.4
    |-- Mailpit
    `-- jobs database <- queue-worker
```

Les sessions et le cache utilisent des fichiers. Redis, Horizon, Sanctum, React, Vue, Livewire et Inertia ne font pas partie du projet.

## Documentation

- [Conventions du projet](AGENTS.md)
- [Décisions techniques](docs/technical-decisions.md)
- [Plan d’implémentation](docs/implementation-plan.md)
- `docs/Cahier_des_Charges_Pepiniere_IA.docx`
- `docs/architecture-pepiniere-ia.png`
