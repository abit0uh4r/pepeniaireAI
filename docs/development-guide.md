# Guide de développement et de reprise

## Prérequis

La voie Docker demande Git et Docker Desktop avec Compose v2. La voie locale demande PHP 8.3, Composer 2, Node.js 22, npm et MySQL 8.4. Les extensions PHP importantes sont `bcmath`, `intl`, `mbstring`, `pdo_mysql`, `pcntl` et `zip`.

## Démarrage avec Docker

Copiez le modèle d’environnement et remplissez les mots de passe locaux :

```powershell
Copy-Item .env.example .env
```

Variables minimales :

```dotenv
DB_PASSWORD=mot-de-passe-local
DB_ROOT_PASSWORD=mot-de-passe-root-local
MANAGER_NAME="Nom du gérant"
MANAGER_EMAIL=manager@example.test
MANAGER_PASSWORD=au-moins-12-caracteres
```

Construisez, démarrez et initialisez :

```bash
docker compose config
docker compose build
docker compose up -d
docker compose exec app php artisan migrate --seed
docker compose ps
```

L’application répond sur `http://localhost:8088`, la connexion sur `/login`, le conseil sur `/conseil`, le healthcheck projet sur `/health` et MySQL sur `127.0.0.1:33060` depuis l’hôte.

## Démarrage sans Docker

Après copie de `.env.example`, remplacez `DB_HOST=mysql` par `DB_HOST=127.0.0.1`. Installez puis initialisez :

```bash
composer install
npm ci
php artisan key:generate
php artisan migrate --seed
npm run build
```

Lancez trois processus dans des terminaux distincts :

```bash
php artisan serve
npm run dev
php artisan queue:work --sleep=3 --tries=3 --timeout=90
```

## Configuration importante

| Variable | Valeur habituelle | Effet |
|---|---|---|
| `QUEUE_CONNECTION` | `database` | Stocke les jobs dans MySQL. |
| `CACHE_STORE` | `file` | Stocke le cache sous `storage/framework/cache`. |
| `SESSION_DRIVER` | `file` | Stocke les sessions sous `storage/framework/sessions`. |
| `MAIL_MAILER` | `log` | Écrit les emails dans les logs. |
| `AI_PROVIDER` | `fake` | Utilise le conseiller déterministe. |
| `ADVICE_MAX_RECOMMENDATIONS` | `3` | Limite le nombre de recommandations persistées. |
| `GROQ_API_KEY` | vide | Clé secrète locale, requise seulement en mode Groq. |
| `GROQ_MODEL` | `openai/gpt-oss-20b` | Modèle demandé au endpoint Groq. |

Après une modification de `.env` dans un environnement qui a mis la configuration en cache :

```bash
php artisan optimize:clear
```

Le worker charge la configuration au démarrage. Il faut donc le redémarrer après un changement de fournisseur ou de clé :

```bash
docker compose restart queue-worker
```

## Activer Groq

Placez la clé uniquement dans `.env` :

```dotenv
AI_PROVIDER=groq
GROQ_API_KEY=votre-cle-locale
```

Puis videz les caches et redémarrez le worker. Ne placez jamais la clé dans `.env.example`, un fichier Markdown, une commande copiée dans un ticket ou un commit.

Pour revenir au mode sans réseau :

```dotenv
AI_PROVIDER=fake
```

Les demandes déjà terminées ne sont pas retraitées. Une nouvelle demande utilise le fournisseur chargé par le worker au moment de son exécution.

## Compte gérant

Création interactive :

```bash
php artisan manager:create
```

Dans Docker :

```bash
docker compose exec app php artisan manager:create
```

Le seeder utilise `MANAGER_*`. Il ne crée rien lorsque `MANAGER_PASSWORD` est vide, afin que le dépôt ne contienne pas d’identifiants par défaut exploitables.

## Comprendre une modification type

Pour ajouter un champ catalogue, il faut en général toucher :

1. une migration pour la colonne ;
2. l’enum si la valeur appartient à un ensemble fermé ;
3. `Plant` pour `Fillable` et le cast ;
4. `PlantRequest` pour la validation ;
5. `_form.blade.php` pour l’interface ;
6. `PlantFactory` et `PlantSeeder` pour les données ;
7. les tests modèle, base et gestion du catalogue.

Pour ajouter une règle de recommandation, modifiez `PlantEligibilityService`, sa configuration éventuelle et ses tests unitaires. Si la règle doit rester vraie après l’appel IA, vérifiez que le Job réutilise `isEligible()` lors de la transaction.

Pour changer le format de réponse du conseiller, mettez à jour l’interface documentée, le fake, Groq, la validation du Job et les tests des deux fournisseurs. Laravel doit rester capable de rejeter une sortie externe invalide.

## Commandes de validation

Contrôle complet local :

```bash
composer validate --strict
php artisan test
vendor/bin/pint --test
npm run build
git diff --check
git status --short
```

Contrôle de la base sur un environnement de développement jetable :

```bash
php artisan migrate:fresh --seed
php artisan migrate:status
```

`migrate:fresh` supprime toutes les tables de la base configurée. Vérifiez la connexion avant de l’exécuter sur une base qui contient des données utiles.

Tests ciblés :

```bash
php artisan test --filter=AdviceRequest
php artisan test --filter=GeneratePlantAdviceJob
php artisan test --filter=PlantEligibilityService
php artisan test --filter=Authentication
```

## Diagnostic de la queue

État et logs Docker :

```bash
docker compose ps queue-worker
docker compose logs --tail=100 queue-worker
```

Consommer un seul job au premier plan :

```bash
docker compose exec app php artisan queue:work --once --tries=3 --timeout=90
```

Inspecter les échecs :

```bash
docker compose exec app php artisan queue:failed
```

Une demande bloquée en `PENDING` indique souvent que le worker ne tourne pas. Une demande `FAILED` avec le message d’indisponibilité indique que le Job a épuisé ses tentatives ou rejeté la sortie du conseiller. Une demande durablement `PROCESSING` après un arrêt brutal demande une intervention manuelle dans la version actuelle ; aucune commande de relance métier n’existe encore.

## Diagnostic HTTP

Liste des routes :

```bash
php artisan route:list --except-vendor
```

Points à vérifier :

- `/register` doit répondre 404 ;
- `/admin` doit rediriger un visiteur vers `/login` ;
- `/health` doit retourner `{"status":"ok", ...}` ;
- un token de suivi doit avoir 64 caractères hexadécimaux ;
- `/conseil/suivi/{token}/status` doit contenir seulement les quatre champs publics prévus.

## Diagnostic Docker

```bash
docker compose config
docker compose ps
docker compose logs --tail=100 app
docker compose logs --tail=100 web
docker compose logs --tail=100 mysql
```

Le healthcheck de `app` exécute `php artisan about --only=environment`. Celui de MySQL utilise `mysqladmin ping`. `web` attend que `app` soit sain ; le worker attend `app` et MySQL.

La commande suivante efface les conteneurs et les volumes, donc toute la base locale et le stockage Laravel :

```bash
docker compose down --volumes
```

Ne l’exécutez qu’après avoir confirmé que ces données peuvent être perdues.

## Diagnostics Intelephense sur `CreateManager.php`

Les messages « Undefined type `Illuminate\\Console\\Command` », « Undefined method `ask` » ou « Undefined function `config` » proviennent en général d’un index PHP incomplet. `Command`, ses méthodes, ses constantes et le helper `config()` appartiennent à Laravel et à ses dépendances sous `vendor/`.

Vérifiez d’abord le projet depuis sa racine :

```bash
composer install
composer dump-autoload
php -l app/Console/Commands/CreateManager.php
php artisan list
```

Dans VS Code, ouvrez le dossier racine `PepiniereIA`, pas seulement `app/`. Vérifiez que `vendor/` n’est pas exclu de l’analyse, puis lancez « Intelephense: Clear Cache » et rechargez la fenêtre. Si `php artisan list` fonctionne et que `php -l` ne signale rien, ces diagnostics concernent l’éditeur et non l’exécution Laravel.

## Logs et emails

Avec `MAIL_MAILER=log`, Laravel écrit le contenu des emails dans `storage/logs/laravel.log` en installation locale classique. Dans Docker, `LOG_CHANNEL=stderr` envoie les logs vers la sortie du conteneur :

```bash
docker compose logs --tail=100 app
```

Mailpit a été retiré du projet. Aucun port SMTP ni interface de consultation d’emails n’est attendu.

## Hygiène Git et secrets

Travaillez sur une branche dédiée et inspectez chaque commit :

```bash
git status --short
git diff
git diff --cached
git ls-files .env
```

`git ls-files .env` ne doit produire aucune sortie. Une clé Groq déjà copiée dans un message ou un fichier suivi doit être révoquée dans la console Groq, même si elle a ensuite été supprimée du dépôt.

## Mise à jour de la documentation

- Une nouvelle décision structurante complète `technical-decisions.md`.
- Un nouveau dossier ou fichier important complète `file-reference.md`.
- Un changement de flux complète `architecture.md`.
- Une nouvelle commande ou variable complète ce guide et le README principal.

Gardez les exemples exécutables. Un lecteur doit pouvoir copier une commande sans devoir deviner le conteneur, le dossier ou les variables attendues.
