# Référence des fichiers et dossiers

## Comment lire cette référence

Les fichiers générés par Laravel, Breeze ou les gestionnaires de dépendances apparaissent aussi dans le dépôt. Cette référence explique chaque fichier applicatif et regroupe les fichiers standard qui partagent le même rôle. `vendor/`, `node_modules/`, les logs et les caches ne sont pas versionnés : Composer, npm et Laravel les recréent.

## Racine du dépôt

| Fichier | Rôle |
|---|---|
| `AGENTS.md` | Conventions obligatoires pour les développeurs et agents : stack, architecture, sécurité, Git et validation. |
| `README.md` | Procédure rapide d’installation, d’utilisation, de démonstration et de test. |
| `artisan` | Point d’entrée CLI de Laravel. Toutes les commandes `php artisan ...` passent par ce fichier. |
| `composer.json` | Dépendances PHP, contrainte PHP 8.3, Laravel 13, scripts Composer et autoload PSR-4. |
| `composer.lock` | Versions PHP exactes résolues. Il garantit le même graphe de dépendances sur chaque machine. |
| `package.json` | Dépendances frontend et scripts Vite `dev`/`build`. |
| `package-lock.json` | Versions npm exactes. `npm ci` s’appuie dessus. |
| `compose.yaml` | Décrit `app`, `web`, `mysql`, `queue-worker`, leur environnement, leurs volumes et healthchecks. |
| `phpunit.xml` | Environnement de test et configuration PHPUnit utilisée par Pest. |
| `vite.config.js` | Relie Vite à Laravel et déclare les entrées CSS et JavaScript. |
| `tailwind.config.js` | Indique à Tailwind quels fichiers Blade/PHP analyser et active le plugin de formulaires. |
| `postcss.config.js` | Chaîne PostCSS : Tailwind et Autoprefixer. |
| `.env.example` | Modèle sans secret des variables nécessaires. On le copie vers `.env`. |
| `.gitignore` | Exclut secrets, dépendances installées, builds et fichiers d’exécution. |
| `.dockerignore` | Réduit le contexte envoyé au build Docker. |
| `.editorconfig` | Règles communes d’encodage, indentation et fins de ligne. |
| `.gitattributes` | Attributs Git et normalisation des fichiers texte. |

## `app/` : code applicatif

### Commande console

`app/Console/Commands/CreateManager.php` déclare `manager:create`. La commande demande le nom, l’email, le mot de passe et sa confirmation, puis délègue la création à `ManagerProvisioner`. Elle renvoie les codes de succès ou d’échec standards de Symfony Console via la classe Laravel `Command`.

### Enums

| Fichier | Valeurs et usage |
|---|---|
| `Enums/AdviceRequestStatus.php` | `PENDING`, `PROCESSING`, `COMPLETED`, `FAILED`, libellés français et détection des états terminaux. |
| `Enums/AdviceFailure.php` | Deux causes applicatives, `NO_ELIGIBLE_PLANTS` et `AI_ERROR`, converties en messages publics. Le code n’est pas stocké en base. |
| `Enums/Exposure.php` | Soleil, mi-ombre ou ombre. |
| `Enums/Level.php` | Niveaux faible, moyen, élevé pour l’arrosage et l’entretien. |
| `Enums/PlantEnvironment.php` | Intérieur, extérieur ou les deux. Le formulaire public n’offre que les deux premiers. |
| `Enums/SpaceSize.php` | Petit, moyen, grand. Ces valeurs indexent les seuils de `config/advice.php`. |

Les backed enums évitent de disperser des chaînes libres dans le code. Eloquent les utilise aussi comme casts.

### Contrôleurs publics et généraux

| Fichier | Responsabilité |
|---|---|
| `Http/Controllers/Controller.php` | Classe parent minimale des contrôleurs. |
| `Http/Controllers/Public/AdviceRequestController.php` | Affiche le formulaire, crée la demande, dispatch le Job, affiche le suivi et fournit son statut JSON minimal. |
| `Http/Controllers/ProfileController.php` | Affiche et modifie le profil Breeze ; contient aussi la suppression du compte. |

### Contrôleurs d’administration

| Fichier | Responsabilité |
|---|---|
| `Http/Controllers/Admin/DashboardController.php` | Autorise l’accès et rend le tableau de bord sans statistiques. |
| `Http/Controllers/Admin/PlantController.php` | Liste, filtre, crée, modifie, désactive, archive et restaure les plantes. |
| `Http/Controllers/Admin/AdviceRequestController.php` | Liste les demandes avec recherche/statut et affiche une demande avec ses recommandations. |

### Contrôleurs d’authentification Breeze

| Fichier | Responsabilité |
|---|---|
| `Auth/AuthenticatedSessionController.php` | Connexion, régénération de session et déconnexion. |
| `Auth/ConfirmablePasswordController.php` | Confirmation du mot de passe avant une action sensible. |
| `Auth/EmailVerificationPromptController.php` | Écran demandant la vérification de l’email. |
| `Auth/EmailVerificationNotificationController.php` | Renvoi de la notification de vérification. |
| `Auth/VerifyEmailController.php` | Vérifie la signature du lien et marque l’email comme validé. |
| `Auth/PasswordResetLinkController.php` | Demande d’un lien de réinitialisation. Avec le driver `log`, le message va dans les logs. |
| `Auth/NewPasswordController.php` | Valide le token et remplace le mot de passe oublié. |
| `Auth/PasswordController.php` | Modifie le mot de passe d’un utilisateur connecté. |

Aucun `RegisteredUserController` n’existe, ce qui confirme la suppression de l’inscription publique.

### Form Requests

| Fichier | Responsabilité |
|---|---|
| `Http/Requests/Public/StoreAdviceRequest.php` | Valide le formulaire public, les enums, la description de 20 à 5 000 caractères et le consentement. |
| `Http/Requests/Admin/PlantRequest.php` | Base commune des règles catalogue et normalisation des valeurs. |
| `Http/Requests/Admin/StorePlantRequest.php` | Autorisation et validation de création d’une plante. |
| `Http/Requests/Admin/UpdatePlantRequest.php` | Autorisation et validation de modification d’une plante existante. |
| `Http/Requests/Auth/LoginRequest.php` | Validation de connexion, authentification et limitation des tentatives. |
| `Http/Requests/ProfileUpdateRequest.php` | Validation du nom et de l’unicité de l’email du profil. |

### Middleware et policies

| Fichier | Responsabilité |
|---|---|
| `Http/Middleware/SecurityHeaders.php` | Ajoute les en-têtes HTTP de sécurité et marque les pages de suivi comme privées et non cachables. |
| `Policies/PlantPolicy.php` | Autorise les actions catalogue au gérant vérifié. |
| `Policies/AdviceRequestPolicy.php` | Autorise la consultation administrative des demandes au gérant vérifié. |

Laravel découvre les policies selon les conventions de nommage, sans enregistrement manuel.

### Modèles

| Fichier | Responsabilité |
|---|---|
| `Models/User.php` | Utilisateur authentifiable, mot de passe haché, email vérifiable et notifications. |
| `Models/Plant.php` | Catalogue, casts, scopes `active`, `inStock`, `search`, relation vers les recommandations et soft delete. |
| `Models/AdviceRequest.php` | Demande publique, génération du token, statut initial, casts et recommandations ordonnées. |
| `Models/PlantRecommendation.php` | Rang, raison, snapshot de stock et relations vers demande/plante, y compris plante archivée. |

Les attributs `#[Fillable]` définissent les champs acceptés par l’assignation de masse. Les méthodes `casts()` convertissent les colonnes en enums, dates, booléens, entiers ou décimaux.

### Services

| Fichier | Responsabilité |
|---|---|
| `Services/PlantAdvisor.php` | Interface commune aux conseillers. Elle constitue le point d’injection du Job. |
| `Services/GroqPlantAdvisor.php` | Client HTTP Groq, prompt français, schéma JSON et traitement des erreurs réseau/JSON. |
| `Services/PlantEligibilityService.php` | Toutes les règles déterministes de sélection et de revalidation des plantes. |
| `Services/ManagerProvisioner.php` | Valide les données du gérant, crée ou met à jour le compte et vérifie son email. |

### Job et support

`Jobs/GeneratePlantAdviceJob.php` orchestre claim, préfiltrage, appel au conseiller, validation, seconde vérification, verrouillage et persistance. Sa méthode `failed()` transforme un échec terminal en message public nettoyé.

`Support/MoneyFormatter.php` convertit une valeur décimale stockée sous forme de chaîne vers un affichage français suivi de `MAD`, sans utiliser de flottant pour le calcul métier.

### Provider et composants de vue PHP

`Providers/AppServiceProvider.php` lie l’interface `PlantAdvisor` à `GroqPlantAdvisor` et refuse toute autre valeur de `config('advice.ai_provider')`. Le reste du code ne contient donc aucun choix de fournisseur.

`View/Components/AppLayout.php` et `View/Components/GuestLayout.php` associent les composants `<x-app-layout>` et `<x-guest-layout>` à leurs vues Blade.

## `bootstrap/`

| Fichier | Rôle |
|---|---|
| `bootstrap/app.php` | Construit l’application Laravel 13, charge les routes, déclare `/up` et ajoute `SecurityHeaders` globalement. |
| `bootstrap/providers.php` | Liste les service providers applicatifs, dont `AppServiceProvider`. |
| `bootstrap/cache/.gitignore` | Conserve le dossier de cache vide dans Git sans versionner son contenu. |

## `config/`

| Fichier | Rôle dans ce projet |
|---|---|
| `advice.php` | Fournisseur IA, configuration Groq, limite de recommandations et seuils d’espace. |
| `manager.php` | Valeurs `MANAGER_NAME`, `MANAGER_EMAIL`, `MANAGER_PASSWORD` utilisées par le seeder et la commande. |
| `app.php` | Nom, environnement, URL, langue française, fuseau et providers de base. |
| `auth.php` | Guard session `web`, provider Eloquent `users` et réinitialisation de mot de passe. |
| `database.php` | Connexions SQLite/MySQL et variables `DB_*`. MySQL sert en local et en CI. |
| `queue.php` | Connexion `database`, paramètres des jobs et table des échecs. |
| `cache.php` | Store `file` par défaut et autres stores disponibles dans Laravel. |
| `session.php` | Driver `file`, durée, cookie et options de sécurité de session. |
| `mail.php` | Mailer `log` par défaut et configurations standard disponibles. |
| `logging.php` | Canaux de logs ; Docker force la sortie standard d’erreur. |
| `filesystems.php` | Disques local, privé et public de Laravel. |
| `services.php` | Emplacements standard pour Postmark, Resend, SES et Slack ; Groq reste dans `advice.php`. |

Seuls les fichiers de configuration appellent `env()`. Le code applicatif lit `config()`, ce qui reste compatible avec `php artisan config:cache`.

## `database/`

### Migrations

| Fichier | Tables ou transformation |
|---|---|
| `0001_01_01_000000_create_users_table.php` | `users` et `password_reset_tokens`. |
| `0001_01_01_000002_create_jobs_table.php` | `jobs` et `failed_jobs`. Aucun batch n’est créé. |
| `2026_07_29_120000_create_plants_table.php` | Schéma initial du catalogue. |
| `2026_07_29_130000_create_advice_requests_table.php` | Schéma initial des demandes. |
| `2026_07_29_140000_create_plant_recommendations_table.php` | Recommandations, rangs et snapshots initiaux. |
| `2026_08_03_190000_simplify_mvp_schema.php` | Convertit l’exposition JSON en enum simple et retire `pet_safe`, `failure_code`, réponse brute IA et snapshot de prix. |
| `2026_08_06_100000_remove_customer_email_from_advice_requests.php` | Retire l’adresse email visiteur des demandes ; le rollback peut recréer la colonne. |

Les migrations de simplification transforment ou retirent les anciennes colonnes. Elles conservent l’historique du schéma au lieu de réécrire les migrations déjà exécutées.

### Seeders

| Fichier | Rôle |
|---|---|
| `seeders/DatabaseSeeder.php` | Lance les seeders gérant et plantes. |
| `seeders/ManagerSeeder.php` | Crée le gérant depuis la configuration si un mot de passe est fourni. |
| `seeders/PlantSeeder.php` | Insère ou met à jour 23 plantes de démonstration par espèce. |

Les seeders sont idempotents : une seconde exécution met à jour les mêmes données au lieu de les dupliquer.

### Factories

`UserFactory.php`, `PlantFactory.php`, `AdviceRequestFactory.php` et `PlantRecommendationFactory.php` créent des données isolées pour les tests. Les états `inactive()`, `outOfStock()` et `completed()` rendent les scénarios métier lisibles.

## `routes/`

| Fichier | Rôle |
|---|---|
| `web.php` | Accueil, santé, conseil public, administration, profil et inclusion des routes auth. |
| `auth.php` | Connexion, déconnexion, vérification d’email et mots de passe. Aucune inscription. |
| `console.php` | Commande de démonstration `inspire`; Laravel découvre `CreateManager` dans `app/Console/Commands`. |

`/health` est un endpoint JSON applicatif nommé. `/up`, déclaré dans `bootstrap/app.php`, est le healthcheck minimal du framework. Aucun des deux ne constitue une API REST métier.

## `resources/`

### Assets

| Fichier | Rôle |
|---|---|
| `css/app.css` | Directives Tailwind, fontes et styles globaux du thème botanique. |
| `js/app.js` | Charge le bootstrap JavaScript et démarre Alpine. |
| `js/bootstrap.js` | Configure Axios avec l’en-tête AJAX attendu par Laravel. |

### Layouts

| Fichier | Rôle |
|---|---|
| `views/layouts/app.blade.php` | Structure des pages gérant authentifiées. |
| `views/layouts/guest.blade.php` | Structure des pages Breeze non authentifiées. |
| `views/components/public-layout.blade.php` | Structure dédiée au parcours visiteur. |
| `views/layouts/navigation.blade.php` | Navigation bureau/mobile du gérant et déconnexion. |

### Pages

| Dossier ou fichier | Rôle |
|---|---|
| `views/welcome.blade.php` | Page d’accueil publique. |
| `views/dashboard.blade.php` | Accueil de l’administration et accès aux modules. |
| `views/advice/create.blade.php` | Formulaire public. |
| `views/advice/track.blade.php` | États, polling Alpine et recommandations finales. |
| `views/admin/plants/` | Catalogue courant, archives restaurables, création, édition et fragment de formulaire. |
| `views/admin/advice-requests/` | Historique et détail d’une demande. |
| `views/auth/` | Pages Breeze de connexion, vérification et mots de passe. |
| `views/profile/` | Profil, changement de mot de passe et suppression du compte. |
| `views/vendor/pagination/tailwind.blade.php` | Pagination Laravel adaptée au thème du projet. |

### Composants Blade

`views/components/` contient le logo, le badge de statut, les champs et labels, les affichages d’erreur, les boutons, les liens de navigation, le dropdown et la modale. Ces composants évitent de recopier le HTML et garantissent le même comportement de focus et d’erreur dans plusieurs pages.

## `public/`

| Fichier | Rôle |
|---|---|
| `index.php` | Front controller : toute requête PHP publique entre ici. |
| `favicon.ico` | Icône du site. |
| `robots.txt` | Instructions destinées aux robots d’indexation. |

Vite produit `public/build/` pendant `npm run build`. Ce dossier dérivé n’a pas besoin d’être modifié à la main.

## `docker/`

| Fichier | Rôle |
|---|---|
| `php/Dockerfile` | Build multi-stage Node, Composer puis PHP-FPM 8.3 avec les extensions nécessaires. |
| `php/entrypoint.sh` | Crée les dossiers d’exécution, ajuste leurs permissions puis lance la commande du conteneur. |
| `nginx/Dockerfile` | Construit l’image du serveur web et copie sa configuration. |
| `nginx/default.conf` | Sert `public/`, transmet les scripts à PHP-FPM et bloque les fichiers cachés. |

## `.github/`

`.github/workflows/ci.yml` décrit l’unique workflow CI. Il tourne sur push et Pull Request, utilise MySQL 8.4 et vérifie hygiène, installation, build, migrations, seeders, tests et Pint.

## `tests/`

| Fichier ou dossier | Couverture |
|---|---|
| `Pest.php` | Configuration globale Pest et traits partagés. |
| `TestCase.php` | Classe de base qui démarre Laravel pour les tests. |
| `Unit/Advice/` | Contrat du conseiller, confidentialité du contexte et client Groq simulé. |
| `Unit/Plants/` | Casts/scopes du modèle et règles de préfiltrage. |
| `Unit/Support/` | Formatage monétaire MAD. |
| `Feature/Advice/` | Soumission, token, suivi, rate limits, affichage et échappement. |
| `Feature/Admin/Plants/` | Autorisations et CRUD du catalogue. |
| `Feature/Admin/AdviceRequests/` | Historique, détail, pagination et snapshot. |
| `Feature/Auth/` | Connexion, inscription absente, vérification et mots de passe. |
| `Feature/Console/` | Commande de création du gérant. |
| `Feature/Database/` | Seeders et schéma simplifié. |
| `Feature/Jobs/` | États, validation IA, idempotence, retries et persistance. |
| `Feature/HealthCheckTest.php` | Endpoint `/health`. |
| `Feature/ProfileTest.php` | Gestion du profil Breeze. |

Les deux fichiers `ExampleTest.php` proviennent du squelette Laravel. Ils vérifient encore un cas minimal, mais peuvent être supprimés lorsque l’équipe ne souhaite plus conserver les exemples de bootstrap.

## `storage/`

Les `.gitignore` conservent la structure des dossiers sans suivre les données d’exécution. Laravel y écrit sessions, cache, vues compilées, logs et fichiers locaux. Le volume Docker `laravel-storage` assure leur persistance et leur partage entre `app` et `queue-worker`.

## `docs/`

- `README.md` indexe les documents techniques.
- `project-overview.md` explique le produit et les choix.
- `architecture.md` explique les couches et les flux.
- `code-walkthrough.md` suit le code avec des extraits commentés.
- `data-model.md` détaille le schéma et les relations.
- `file-reference.md` décrit le dépôt fichier par fichier.
- `development-guide.md` aide à installer, tester et dépanner.
- `glossary.md` définit le vocabulaire Laravel du projet.
- `chatgpt-project-context.md` fournit un contexte autonome pour préparer une évolution avec ChatGPT.
- `technical-decisions.md` conserve les arbitrages numérotés.
- `implementation-plan.md` conserve l’historique du découpage en phases.
- le DOCX et le PNG sont les sources de cadrage initiales.
