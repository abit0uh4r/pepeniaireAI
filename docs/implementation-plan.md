# Plan

Le projet sera construit par tranches courtes qui laissent toujours le dépôt dans un état testable. Les règles de préfiltrage et de validation défensive précèdent tout appel à Groq. Les tests remplacent l’Agent Laravel AI par son fake intégré afin de rester sans réseau.

## Périmètre

- Inclus : initialisation Laravel, authentification du gérant, catalogue et stock, demandes publiques, queue database, Agent `laravel/ai` pour Groq, préfiltrage, validation et persistance, consultation par token, administration, Docker, CI et documentation.
- Hors périmètre des phases initiales avant la phase 9 : activation opérationnelle de Groq, inscription publique, comptes visiteurs, paiement, réservation, API REST séparée, SPA, pièces jointes et envoi d’email.
- Reporté après le MVP : relance manuelle, email du lien public, sécurité animale, snapshots de prix, comparaison de stock, filtres par période et statistiques.

## Arborescence Laravel cible

```text
app/
├── Console/Commands/
├── Enums/
├── Http/
│   ├── Controllers/
│   └── Requests/
├── Jobs/
│   └── GeneratePlantAdviceJob.php
├── Ai/
│   └── Agents/
│       └── PlantAdviceAgent.php
├── Models/
├── Policies/
├── Providers/
└── Services/
    ├── GroqPlantAdvisor.php
    ├── PlantAdvisor.php
    └── PlantEligibilityService.php
database/
├── factories/
├── migrations/
└── seeders/
resources/views/
├── admin/
├── advice/
├── auth/
├── components/
└── layouts/
tests/
├── Feature/
└── Unit/
```

Les dossiers `Actions`, `Contracts` et `DTOs` sont exclus du MVP. La validation de la réponse IA appartient à `GeneratePlantAdviceJob`.

## Action items

## Phases d’implémentation

### Phase 0 : Cadrage

Résultat : conventions, décisions et plan validés avant toute installation.

- [x] Lire le cahier des charges et le diagramme.
- [x] Relever les contradictions et choisir des arbitrages.
- [x] Définir l’arborescence cible.
- [x] Découper le travail et ses contrôles.
- [ ] Faire valider les décisions par le porteur du projet.

Validation :

```bash
git diff --check
git status --short
```

### Phase 1 : Initialiser le socle Laravel

Résultat : Laravel 13 vide fonctionne avec PHP 8.3, Blade, Pest et les réglages locaux imposés. Aucun modèle métier n’est créé.

- [ ] Installer Laravel 13 dans le dépôt existant sans écraser `docs/` ni `AGENTS.md`.
- [ ] Fixer la contrainte PHP à `^8.3`.
- [ ] Vérifier la présence de Pest et configurer la suite de base.
- [ ] Configurer MySQL, les drivers file, la queue database et le driver mail `log` dans `.env.example`.
- [ ] Générer uniquement les migrations `jobs` et `failed_jobs` nécessaires.
- [ ] Préparer `config/advice.php` avec la limite de trois recommandations et les paramètres Groq.
- [ ] Confirmer l’absence de React, Vue, Livewire, Inertia, Sanctum, Redis et Horizon.

Validation :

```bash
php -v
composer validate --strict
php artisan about
php artisan config:show queue
php artisan config:show cache
php artisan config:show session
php artisan migrate:fresh
php artisan test
npm run build
composer show
```

Critère de sortie : application Laravel vierge, test de démarrage vert, migrations techniques exécutables sur MySQL 8.4 et aucun code métier.

### Phase 2 : Conteneuriser l’environnement

Résultat : l’application, MySQL 8.4 et le worker démarrent avec Docker Compose.

- [ ] Créer l’image PHP 8.3 commune à `app` et `worker`.
- [ ] Ajouter Nginx et MySQL 8.4.
- [ ] Ajouter le profil ou service Node pour Vite.
- [ ] Ajouter les healthchecks et dépendances de démarrage.
- [ ] Vérifier l’écriture de `storage/` et le partage nécessaire avec le worker.

Validation :

```bash
docker compose config
docker compose build
docker compose up -d
docker compose ps
docker compose exec app php artisan about
docker compose exec app php artisan migrate:fresh
docker compose exec app php artisan test
docker compose exec app php artisan queue:work --once
```

Critère de sortie : tous les services attendus sont sains et un Job technique de test peut être consommé sans Redis.

### Phase 3 : Ajouter Breeze Blade et verrouiller l’administration

Résultat : le gérant se connecte par session et aucune inscription publique n’existe.

- [ ] Installer Breeze avec le stack Blade.
- [ ] Supprimer routes, vues et tests d’inscription.
- [ ] Protéger `/admin` avec `auth`.
- [ ] Ajouter la commande interactive de création du premier gérant.
- [ ] Ajouter les tests d’accès, connexion, déconnexion et absence d’inscription.

Validation :

```bash
php artisan route:list
php artisan test --filter=Authentication
php artisan test --filter=Registration
vendor/bin/pint --test
npm run build
```

Critère de sortie : `/admin` redirige un visiteur, un gérant se connecte, et aucune route `register` n’existe.

### Phase 4 : Construire le catalogue et le stock

Résultat : le gérant administre des plantes validées sans perdre l’historique futur.

- [x] Créer enums, migration, modèle, factory et seeder des plantes.
- [x] Implémenter les contraintes de base, casts, soft delete et index.
- [x] Ajouter Form Requests, policy et CRUD Blade.
- [x] Ajouter recherche, filtres et pagination.
- [x] Ajouter une page d’administration des plantes archivées et une restauration contrôlée.
- [x] Traiter le stock et l’activité de façon explicite ; reporter `pet_safe` après le MVP.
- [x] Tester création, modification, désactivation, suppression logique et autorisations.

Validation :

```bash
php artisan migrate:fresh --seed
php artisan test tests/Feature/Admin/Plants
php artisan test tests/Unit/Plants
vendor/bin/pint --test
npm run build
```

Critère de sortie : le catalogue fonctionne de bout en bout et une personne non authentifiée ne peut pas le modifier.

### Phase 5 : Enregistrer et exposer les demandes publiques

Résultat : un visiteur soumet une demande, reçoit une URL par token et peut suivre son état sans attendre un traitement.

- [ ] Valider les choix encore ouverts sur les seuils d’espace et les données de contact.
- [x] Créer enums, migration, modèle et factory de demande.
- [x] Ajouter le formulaire public et sa Form Request.
- [x] Générer le token public sécurisé.
- [x] Créer et exposer l’état `PENDING`.
- [ ] Dispatcher le Job (reporté à la phase 6).
- [x] Ajouter la page Blade de suivi et le point de polling limité.
- [x] Tester validation, rate limiting, token valide/invalide et absence de fuite d’identifiant.

Le dispatch du Job est volontairement reporté à la phase 6 dédiée à la queue et à l’intégration du conseiller Groq.

Validation :

```bash
php artisan migrate:fresh --seed
php artisan route:list --path=conseil
php artisan test tests/Feature/Advice/SubmitAdviceRequestTest.php
php artisan test tests/Feature/Advice/PublicAdviceResultTest.php
vendor/bin/pint --test
npm run build
```

Critère de sortie : la soumission retourne vite, crée `PENDING`, redirige vers un token non prédictible et expose uniquement un suivi public borné. Le traitement asynchrone est ajouté en phase 6.

Le découpage opérationnel validé pour la suite est : phase 6 queue et conseiller Groq, phase 7 préfiltrage métier, phase 8 validation et persistance des recommandations, phase 9 fournisseur Groq, phase 10 sécurité/tests/CI et phase 11 interface finale/démonstration.

### Phase 7 : Implémenter le préfiltrage Laravel

Résultat : Laravel produit une liste déterministe de candidates éligibles avant tout conseiller.

- [ ] Implémenter `PlantEligibilityService`.
- [x] Couvrir activité, stock, environnement, exposition unique, espace et entretien.
- [ ] Centraliser les seuils d’espace dans la configuration.
- [ ] Tester les combinaisons limites, les valeurs inconnues et la liste vide.

Validation :

```bash
php artisan test tests/Unit/Plants/PlantEligibilityServiceTest.php
php artisan test --filter=PlantEligibility
vendor/bin/pint --test
```

Critère de sortie : chaque règle BR-01 à BR-07 dispose d’au moins un test positif et négatif.

### Phase 8 : Valider et persister les recommandations

Résultat : Laravel revalide la sortie du conseiller dans le Job et conserve uniquement les recommandations sûres avec le snapshot de quantité.

- [x] Créer la migration et le modèle des recommandations avec snapshot de quantité et contrainte unique.
- [x] Intégrer la validation défensive directement dans `GeneratePlantAdviceJob`.
- [x] Orchestrer le Job avec transitions, retries bornés, idempotence et transaction finale courte.
- [x] Produire `FAILED` avec le message `NO_ELIGIBLE_PLANTS` sans appeler le fake lorsqu’aucune candidate n’existe.
- [x] Rejeter les identifiants inconnus, doublons, plantes inactives ou en rupture et limiter les résultats.

Validation :

```bash
php artisan migrate:fresh --seed
php artisan test tests/Unit/Advice
php artisan test tests/Feature/Jobs/GeneratePlantAdviceJobTest.php
php artisan queue:work --once
vendor/bin/pint --test
```

Critère de sortie : les tests écartent un ID inventé, une rupture, une plante inactive, un doublon et un replay du Job.

### Phase 11 : Finaliser l’interface et la démonstration

Résultat : le visiteur consulte le résultat et le gérant audite les demandes.

- [x] Afficher les quatre états sur la page publique.
- [x] Afficher résumé, raisons, entretien et snapshot de quantité.
- [x] Ajouter un accueil d’administration, un historique simple et un détail de demande.
- [x] Vérifier l’échappement des textes issus du conseiller.
- [x] Vérifier l’accessibilité clavier et la présentation mobile.

Validation :

```bash
php artisan test tests/Feature/Advice
php artisan test tests/Feature/Admin/AdviceRequests
php artisan test
vendor/bin/pint --test
npm run build
```

Critère de sortie : le parcours catalogue → demande → worker → résultat est démontrable avec Groq et une clé locale configurée.

### Phase 10 : Sécurité, tests et CI

Résultat : Docker et GitHub Actions reproduisent les contrôles du projet.

- [x] Maintenir le workflow GitHub Actions avec PHP 8.3, MySQL 8.4 et Node.
- [x] Mettre en cache les dépendances sans masquer les erreurs.
- [x] Lancer migrations, Pest, Pint, audit Composer et build Vite.
- [x] Vérifier les en-têtes de sécurité, secrets, rate limits et messages publics.
- [x] Documenter installation, création du gérant, worker et démonstration.

Validation :

```bash
composer validate --strict
composer audit
php artisan migrate:fresh --seed
php artisan test
vendor/bin/pint --test
npm run build
docker compose config
docker compose exec app php artisan test
git diff --check
```

Critère de sortie : les mêmes contrôles passent localement et dans GitHub Actions, sans réseau IA.

### Phase 9 : Intégrer Groq via le SDK `laravel/ai`

Résultat : `GroqPlantAdvisor` utilise l’Agent Laravel AI avec Groq par configuration, sans modifier les règles métier.

- [x] Installer `laravel/ai` et publier sa configuration.
- [x] Créer un Agent Laravel avec instructions francophones et structured output.
- [x] Configurer Groq, le modèle, le timeout et les limites de tokens.
- [x] Retourner un tableau structuré que le Job revalide.
- [x] Ajouter des tests avec `PlantAdviceAgent::fake()` sans appel réseau.
- [x] Lier `PlantAdvisor` à `GroqPlantAdvisor` comme fournisseur unique.
- [ ] Effectuer un test manuel Groq, hors CI, avec une clé fournie localement.

Validation :

```bash
php artisan test --filter=GroqPlantAdvisor
php artisan test
vendor/bin/pint --test
composer audit
```

Critère de sortie : la suite automatisée reste sans réseau, et le fournisseur réel ne peut contourner aucune validation Laravel.

## Risques à vérifier à chaque phase

- Une dépendance ajoutée introduit un composant interdit ou une version incompatible.
- Une route publique révèle un identifiant, une donnée personnelle ou une erreur technique.
- Une migration permet de casser l’historique ou de créer des doublons.
- Un test d’Agent n’utilise pas `PlantAdviceAgent::fake()` et tente un appel réseau.
- Un retry laisse une demande bloquée en PROCESSING.
- Une modification de stock entre l’appel IA et la transaction finale invalide une candidate.
- Le polling surcharge l’application ou continue après un état terminal.

## Questions ouvertes non bloquantes pour la phase 1

1. Quelles limites de hauteur et de largeur définissent SMALL, MEDIUM et LARGE ?
2. Le formulaire MVP conserve uniquement un prénom facultatif en complément des contraintes de conseil ; aucune adresse email visiteur n’est collectée.
3. Le service web Docker doit-il utiliser Nginx, comme proposé, ou Laravel Octane/PHP intégré pour la démonstration ?

Le plan retient Nginx et reporte les deux premières réponses à la phase 5 si aucune décision n’intervient avant.
