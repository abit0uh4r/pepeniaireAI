# Pépinière IA : conventions de travail

## Portée

Ce dépôt contient une application web monolithique Laravel. Toute contribution doit respecter le cahier des charges dans `docs/Cahier_des_Charges_Pepiniere_IA.docx`, les décisions dans `docs/technical-decisions.md` et le plan dans `docs/implementation-plan.md`.

En cas de contradiction, appliquer cet ordre :

1. demande explicite validée par le porteur du projet ;
2. `docs/technical-decisions.md` ;
3. présent fichier ;
4. cahier des charges ;
5. diagramme d’architecture.

Documenter toute nouvelle décision structurante dans `docs/technical-decisions.md` avant de l’implémenter.

## Stack imposée

- Laravel 13 et PHP 8.3 ;
- application Blade rendue côté serveur ;
- Tailwind CSS ;
- Alpine.js pour une interactivité locale et légère ;
- MySQL 8.4 ;
- authentification du gérant par session avec Laravel Breeze, stack Blade ;
- inscription publique désactivée ;
- `QUEUE_CONNECTION=database`, avec `jobs` et `failed_jobs` ;
- `CACHE_STORE=file` ;
- `SESSION_DRIVER=file` ;
- email local via le driver `log` ;
- Pest pour les tests ;
- Docker Compose pour l’environnement local ;
- GitHub Actions pour l’intégration continue ;
- `FakePlantAdvisor` par défaut en développement et dans les tests ;
- `GroqPlantAdvisor` disponible uniquement par activation explicite ;

## Choix interdits

Ne pas introduire React, Vue, Livewire, Inertia, Redis, Horizon, une SPA, des microservices ou une API REST séparée pour le MVP. Ne pas ajouter Sanctum sans un besoin nouveau, démontré et validé.

Ne pas laisser un fournisseur IA écrire dans la base, modifier le stock, choisir une plante absente des candidates ou enrichir une propriété botanique manquante.

## Architecture applicative

- Les contrôleurs valident l’accès, délèguent le travail et choisissent la réponse HTTP. Ils ne portent pas les règles métier.
- Les Form Requests valident et normalisent les entrées HTTP.
- Les Policies et les middlewares contrôlent l’accès à l’administration.
- `PlantEligibilityService` applique toutes les contraintes déterministes avant l’appel au conseiller.
- `PlantAdvisor`, `FakePlantAdvisor`, `GroqPlantAdvisor` et `PlantEligibilityService` résident directement dans `app/Services`.
- Ne pas créer de dossiers `Actions`, `Contracts` ou `DTOs` pour le MVP.
- Le Job orchestre le traitement et revalide lui-même la structure IA, les identifiants candidats, l’état actif, le stock, les doublons et la limite de résultats.
- Une transaction courte persiste le résultat final. Aucun appel externe ne s’exécute dans une transaction SQL.
- Les modèles Eloquent décrivent les relations, casts et scopes simples. Ils ne deviennent pas des services globaux.

Le flux métier obligatoire reste :

`requête validée → demande PENDING → Job → préfiltrage Laravel → PlantAdvisor → validation Laravel → transaction → COMPLETED`

Une erreur terminale produit `FAILED`. Une absence de candidate produit `FAILED` avec le message public correspondant à `NO_ELIGIBLE_PLANTS`, sans appel au conseiller.

## Source de vérité et intégrité

- Laravel et MySQL restent les sources de vérité.
- Envoyer au conseiller uniquement les candidates éligibles et les champs utiles.
- Référencer les candidates par leur identifiant de base de données.
- Recharger et verrouiller les données utiles avant la persistance finale.
- Vérifier une seconde fois l’activité, l’éligibilité et `stock_quantity > 0`.
- Dédupliquer les recommandations et appliquer la limite configurée.
- Conserver uniquement la quantité observée dans un snapshot pour le MVP.
- Protéger l’historique avec les clés étrangères, les contraintes uniques et la suppression logique des plantes.
- Rendre le Job rejouable sans créer de doublon.

## Conventions PHP et Laravel

- Respecter PSR-12 et le style Laravel, contrôlé par Laravel Pint.
- Activer `declare(strict_types=1);` dans les nouveaux fichiers PHP applicatifs.
- Utiliser des types de retour, des propriétés typées et des backed enums.
- Nommer les classes et méthodes en anglais ; rédiger les textes visibles et la documentation produit en français.
- Utiliser les conventions Laravel pour les tables, clés étrangères, relations et routes nommées.
- Préférer l’injection par constructeur aux façades dans les services métier.
- Accéder aux variables d’environnement uniquement depuis les fichiers de configuration.
- Ne jamais appeler `env()` depuis le code applicatif.
- Garder les changements ciblés. Ne pas reformater un fichier sans rapport avec la tâche.

## Base de données

- Écrire des migrations réversibles et exécutables sur une base vide MySQL 8.4.
- Ajouter les clés étrangères, contraintes uniques, valeurs par défaut et index utiles dans les migrations.
- Utiliser des colonnes `VARCHAR` associées à des backed enums PHP pour les états et niveaux métier.
- Stocker une exposition unique dans une colonne `ENUM` simple, castée vers `Exposure`.
- Reporter `pet_safe` et la règle BR-06 après le MVP.
- Utiliser des montants décimaux. Ne pas employer de nombres flottants pour les prix.
- Éviter les suppressions en cascade qui effaceraient l’historique métier.
- Ne créer ni table `sessions` ni table `cache` tant que les drivers restent sur fichiers.
- Ne créer `job_batches` que si une fonctionnalité de batch est introduite et validée.

## Authentification et sécurité

- Placer toutes les routes `/admin` derrière le middleware `auth`.
- Ne publier aucune route d’inscription.
- Provisionner le premier gérant par une commande Artisan dédiée, jamais par un formulaire public.
- Protéger les formulaires avec CSRF et échapper les textes IA avec la syntaxe Blade standard.
- Identifier une demande publique avec un token aléatoire de 32 octets encodé en hexadécimal et unique.
- Ne jamais exposer l’identifiant numérique d’une demande dans une URL publique.
- Limiter le débit de création, de consultation et de polling.
- Ne pas envoyer le nom ou l’adresse email du visiteur au conseiller.
- Ne pas journaliser de clé, secret, payload personnel complet ou réponse brute non nettoyée.

## Conseillers IA

- Lier `PlantAdvisor` à `FakePlantAdvisor` dans les environnements local et test.
- Produire des réponses déterministes dans le fake ; couvrir aussi les identifiants inconnus, doublons et sorties invalides.
- Garder toute dépendance Groq hors des phases initiales.
- Valider la sortie du conseiller indépendamment de la validation éventuelle du SDK.
- Ne jamais faire confiance au rang, au stock, au prix, au nom ou aux propriétés renvoyés par l’IA.

## Files d’attente

- Utiliser la connexion `database`.
- Définir un timeout et un nombre de tentatives borné pour chaque Job.
- Persister uniquement un `failure_message` nettoyé. Les deux causes applicatives sont `NO_ELIGIBLE_PLANTS` et `AI_ERROR`.
- Prévoir les exécutions concurrentes, retries et redémarrages du worker.
- Utiliser les contraintes SQL comme dernier rempart contre les doublons.
- Garder la page publique indépendante de l’état du worker : elle affiche PENDING, PROCESSING, COMPLETED ou FAILED.

## Frontend

- Rendre toutes les pages avec Blade.
- Utiliser Alpine.js uniquement pour le polling, l’ouverture de menus ou des interactions comparables.
- Utiliser les composants Blade pour les champs, erreurs, boutons, badges et mises en page récurrents.
- Conserver une navigation utilisable sans dépendre d’un bundle JavaScript applicatif complexe.
- Associer chaque champ à un label et chaque erreur au champ concerné.
- Vérifier le clavier, le focus, les contrastes et les vues mobiles.
- Le point de statut JSON sert au polling de la page Blade ; il ne constitue pas une API publique séparée.

## Tests

- Écrire les tests avec Pest.
- Ajouter un test pour chaque règle métier, autorisation, transition d’état et cas d’échec modifiés.
- Utiliser les factories pour préparer les données.
- Utiliser `Queue::fake()` pour tester le dispatch et le vrai Job avec `FakePlantAdvisor` pour tester l’intégration.
- Interdire tout appel réseau dans la suite de tests.
- Couvrir au minimum : plante inactive, stock nul, identifiant inventé, doublon, dépassement de limite, absence de candidate, sortie invalide, retry et exécution concurrente.
- Vérifier le snapshot de quantité sans comparaison avec le stock courant dans l’interface MVP.

## Commandes de contrôle

Exécuter les commandes pertinentes avant de déclarer une phase terminée :

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
npm run build
php artisan migrate:fresh --seed
```

Pour l’environnement conteneurisé :

```bash
docker compose config
docker compose build
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed
docker compose exec app php artisan test
docker compose ps
```

Les commandes exactes peuvent évoluer avec l’infrastructure. Mettre à jour ce fichier et le README ensemble.

## Définition de terminé

Une phase est terminée lorsque :

- son périmètre et ses exclusions sont respectés ;
- les migrations repartent d’une base vide ;
- les tests de la phase passent sans réseau ;
- Pint ne signale aucun écart ;
- les assets se construisent ;
- les erreurs et autorisations prévues sont couvertes ;
- la documentation et `.env.example` reflètent les nouveaux réglages ;
- aucun secret, code temporaire ou dépendance hors périmètre n’a été ajouté.
