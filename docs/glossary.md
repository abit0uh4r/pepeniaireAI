# Glossaire Laravel du projet

Ce glossaire donne le sens des termes employés dans le code et la documentation.

## Application monolithique

Une seule application Laravel gère les pages, l’authentification, le métier, la base et les jobs. Le projet ne possède ni backend API séparé ni frontend SPA.

## Alpine.js

Petite bibliothèque JavaScript utilisée dans les vues Blade. Elle gère le menu mobile, les modales et le polling de la page de suivi. Elle ne remplace pas Blade.

## Artisan

Interface en ligne de commande de Laravel. `php artisan migrate`, `php artisan test` et `php artisan queue:work` passent par le fichier `artisan` à la racine.

## Backed enum

Enum PHP associé à une valeur stockable, comme `PENDING` ou `SUN`. Il évite les chaînes libres et regroupe les valeurs autorisées avec leurs libellés.

## Blade

Moteur de templates Laravel. Un fichier `.blade.php` mélange du HTML avec des directives telles que `@if`, `@foreach` et `@csrf`. Laravel génère le HTML sur le serveur.

## Breeze

Kit d’authentification Laravel installé avec le stack Blade. Il fournit la connexion, la déconnexion, la gestion du mot de passe, la vérification d’email du gérant et le profil.

## Cache fichier

Laravel écrit le cache dans `storage/framework/cache` avec `CACHE_STORE=file`. Le projet n’a pas besoin de Redis pour le MVP.

## Cast Eloquent

Conversion appliquée par un modèle. Par exemple, Eloquent transforme `stock_quantity` en entier et `status` en `AdviceRequestStatus`.

## CI

Intégration continue exécutée par GitHub Actions. Une machine neuve installe le projet, construit les assets, migre MySQL, lance les tests et contrôle le formatage.

## Composant Blade

Fragment d’interface réutilisable, comme `<x-input-error>` ou `<x-advice-status-badge>`. Il centralise le HTML et les classes Tailwind d’un élément commun.

## Container de services

Mécanisme Laravel qui construit les objets et injecte leurs dépendances. `AppServiceProvider` lui indique quelle classe fournir lorsqu’un Job demande un `PlantAdvisor`.

## Contrôleur

Classe appelée par une route. Il reçoit la requête, délègue la validation et le métier, puis retourne une vue, une redirection ou une réponse JSON.

## CSRF

Protection des formulaires web. `@csrf` place un jeton dans le formulaire. Laravel refuse une requête POST dont le jeton ne correspond pas à la session.

## Docker Compose

Fichier qui décrit les conteneurs locaux. Le projet lance `web`, `app`, `mysql` et `queue-worker` avec `compose.yaml`.

## Eloquent

ORM de Laravel. Un modèle comme `Plant` représente une table et permet d’écrire `Plant::query()->active()->get()` au lieu de construire chaque requête SQL à la main.

## Factory

Classe qui crée des données pour les tests. `PlantFactory` produit une plante valide, puis un test peut remplacer son stock ou son exposition.

## Fake

Implémentation de test qui remplace un service externe. `FakePlantAdvisor` classe les candidates sans appeler Internet.

## Form Request

Classe Laravel consacrée à la validation et à l’autorisation d’un formulaire. `StoreAdviceRequest` protège le contrôleur public contre les champs absents ou invalides.

## Guard `web`

Configuration d’authentification basée sur la session et les cookies. Breeze l’utilise pour reconnaître le gérant connecté.

## Healthcheck

Vérification technique qui confirme qu’un service répond. `/health` contrôle l’application ; Docker vérifie aussi PHP et MySQL avant de démarrer les services dépendants.

## Injection de dépendances

Laravel fournit un objet demandé dans les paramètres d’une méthode ou d’un constructeur. Le Job reçoit `PlantAdvisor` et `PlantEligibilityService` sans les créer lui-même.

## Job

Classe qui représente un travail différé. `GeneratePlantAdviceJob` préfiltre les plantes, appelle le conseiller, valide sa réponse et persiste les recommandations.

## Middleware

Filtre exécuté autour d’une route. `auth` exige une connexion, `verified` exige un gérant vérifié et `throttle` limite le nombre de requêtes.

## Migration

Fichier PHP qui crée ou modifie le schéma de base de données. `up()` applique le changement et `down()` l’annule.

## Modèle

Classe Eloquent qui représente une entité persistée. Le projet possède `User`, `Plant`, `AdviceRequest` et `PlantRecommendation`.

## Nginx

Serveur web placé devant PHP-FPM dans Docker. Il sert les fichiers publics et transmet les requêtes PHP au conteneur `app`.

## ORM

Couche qui relie objets PHP et lignes SQL. Eloquent est l’ORM de Laravel.

## Pest

Outil de test PHP utilisé par le projet. Il s’appuie sur PHPUnit et propose une syntaxe courte avec `test()`, `expect()` et les méthodes HTTP Laravel.

## PHP-FPM

Processus PHP utilisé par Nginx. Le service Docker `app` exécute Laravel avec PHP-FPM 8.3.

## Pint

Formateur officiel de Laravel. `vendor/bin/pint --test` signale les écarts de style sans modifier les fichiers.

## Policy

Classe d’autorisation liée à un modèle. `PlantPolicy` décide si le gérant peut voir, créer, modifier ou archiver une plante.

## Polling

Requête répétée par le navigateur pour connaître un nouvel état. La page de suivi appelle le point de statut toutes les cinq secondes, puis arrête lorsque la demande atteint `COMPLETED` ou `FAILED`.

## Provider

Classe qui enregistre des services au démarrage de Laravel. `AppServiceProvider` associe `PlantAdvisor` au fake ou à Groq.

## Queue

File de travaux asynchrones. La connexion `database` place les jobs dans MySQL. Le worker les récupère en dehors de la requête web.

## Rate limit

Nombre maximal de requêtes autorisées pendant une période. Le projet limite les soumissions et les consultations publiques pour réduire les abus.

## Relation Eloquent

Lien entre deux modèles. Une `AdviceRequest` possède plusieurs recommandations ; chaque recommandation appartient à une plante.

## Route

Association entre une méthode HTTP, une URL et du code Laravel. Une route nommée peut être référencée avec `route('advice.store')`.

## Seeder

Classe qui remplit la base avec des données initiales. `PlantSeeder` ajoute le catalogue de démonstration et `ManagerSeeder` provisionne le gérant configuré.

## Session

Données liées au navigateur connecté. Laravel les stocke dans des fichiers pour ce projet. La session porte l’identité du gérant, les erreurs de formulaire et les messages temporaires.

## Soft delete

Archivage par remplissage de `deleted_at`. La ligne reste en base et l’historique conserve ses références.

## Snapshot

Copie d’une valeur au moment d’un événement. `stock_quantity_snapshot` garde la quantité observée lors de la recommandation, même si le gérant modifie ensuite le stock.

## Tailwind CSS

Framework CSS basé sur des classes utilitaires. Les vues combinent des classes comme `rounded-xl`, `text-sm` et `bg-emerald-950`.

## Token public

Chaîne aléatoire de 64 caractères placée dans l’URL de suivi. Elle donne accès à une demande sans exposer son identifiant SQL.

## Transaction SQL

Bloc d’écritures validé en une seule fois. Si une exception survient, MySQL annule les changements du bloc.

## Vite

Outil qui compile `resources/css/app.css` et `resources/js/app.js` vers `public/build`. `npm run dev` sert les assets pendant le développement et `npm run build` produit les fichiers de déploiement.

## Worker

Processus qui consomme la queue. Le service `queue-worker` exécute les demandes de conseil en arrière-plan.
