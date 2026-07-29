# Plan

Le projet sera construit par tranches courtes qui laissent toujours le dépôt dans un état testable. Le fake déterministe précède toute intégration Groq, et les règles de préfiltrage comme la validation défensive précèdent tout appel à un fournisseur réel.

## Périmètre

- Inclus : initialisation Laravel, authentification du gérant, catalogue et stock, demandes publiques, queue database, fake IA, préfiltrage, validation et persistance, consultation par token, administration, Docker, CI et documentation.
- Hors périmètre initial : Groq, inscription publique, comptes visiteurs, paiement, réservation, API REST séparée, SPA, pièces jointes et envoi d’email.
- Reporté après le socle MVP : GroqPlantAdvisor, relance manuelle et email du lien public.

## Arborescence Laravel cible

```text
.
├── .github/
│   └── workflows/
│       └── ci.yml
├── app/
│   ├── Actions/
│   │   └── Advice/
│   │       ├── CompleteAdviceRequest.php
│   │       └── SubmitAdviceRequest.php
│   ├── Console/
│   │   └── Commands/
│   │       └── CreateManager.php
│   ├── Contracts/
│   │   └── AI/
│   │       └── PlantAdvisor.php
│   ├── DTOs/
│   │   └── Advice/
│   │       ├── AdviceContext.php
│   │       ├── AdviceResult.php
│   │       └── PlantRecommendationData.php
│   ├── Enums/
│   │   ├── AdviceFailureCode.php
│   │   ├── AdviceRequestStatus.php
│   │   ├── Exposure.php
│   │   ├── Level.php
│   │   ├── PlantEnvironment.php
│   │   └── SpaceSize.php
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Admin/
│   │   │   │   ├── AdviceRequestController.php
│   │   │   │   ├── DashboardController.php
│   │   │   │   └── PlantController.php
│   │   │   └── Public/
│   │   │       ├── AdviceRequestController.php
│   │   │       └── AdviceResultController.php
│   │   └── Requests/
│   │       ├── Admin/
│   │       │   ├── StorePlantRequest.php
│   │       │   └── UpdatePlantRequest.php
│   │       └── Public/
│   │           └── StoreAdviceRequest.php
│   ├── Jobs/
│   │   └── GeneratePlantAdviceJob.php
│   ├── Models/
│   │   ├── AdviceRecommendation.php
│   │   ├── AdviceRequest.php
│   │   ├── Plant.php
│   │   └── User.php
│   ├── Policies/
│   │   ├── AdviceRequestPolicy.php
│   │   └── PlantPolicy.php
│   ├── Providers/
│   │   └── AppServiceProvider.php
│   └── Services/
│       ├── AI/
│       │   ├── AdviceResultValidator.php
│       │   └── FakePlantAdvisor.php
│       └── Plants/
│           └── PlantEligibilityService.php
├── bootstrap/
├── config/
│   └── advice.php
├── database/
│   ├── factories/
│   ├── migrations/
│   └── seeders/
├── docker/
│   ├── nginx/
│   │   └── default.conf
│   └── php/
│       └── Dockerfile
├── docs/
├── public/
├── resources/
│   ├── css/
│   ├── js/
│   └── views/
│       ├── admin/
│       │   ├── advice-requests/
│       │   ├── plants/
│       │   └── dashboard.blade.php
│       ├── advice/
│       │   ├── create.blade.php
│       │   └── show.blade.php
│       ├── auth/
│       ├── components/
│       └── layouts/
├── routes/
│   ├── auth.php
│   ├── console.php
│   └── web.php
├── storage/
├── tests/
│   ├── Feature/
│   │   ├── Admin/
│   │   ├── Advice/
│   │   ├── Auth/
│   │   └── Jobs/
│   ├── Unit/
│   │   ├── Advice/
│   │   └── Plants/
│   └── Pest.php
├── AGENTS.md
├── compose.yaml
├── composer.json
├── package.json
├── phpunit.xml
└── README.md
```

`GroqPlantAdvisor.php` n’entre dans cette arborescence qu’après validation de la phase dédiée.

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
- [ ] Configurer MySQL, les drivers file, la queue database et Mailpit dans `.env.example`.
- [ ] Générer uniquement les migrations `jobs` et `failed_jobs` nécessaires.
- [ ] Préparer `config/advice.php` avec `AI_PROVIDER=fake` et la limite de trois recommandations.
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

Résultat : l’application, MySQL 8.4, le worker et Mailpit démarrent avec Docker Compose.

- [ ] Créer l’image PHP 8.3 commune à `app` et `worker`.
- [ ] Ajouter Nginx, MySQL 8.4 et Mailpit.
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
- [x] Traiter le stock, l’activité et `pet_safe = null` de façon explicite.
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

Résultat : un visiteur soumet une demande et reçoit une URL par token sans attendre un traitement.

- [ ] Valider les choix encore ouverts sur les seuils d’espace et les données de contact.
- [ ] Créer enums, migration, modèle et factory de demande.
- [ ] Ajouter le formulaire public et sa Form Request.
- [ ] Générer le token public sécurisé.
- [ ] Créer l’état PENDING et dispatcher le Job.
- [ ] Ajouter la page Blade de suivi et le point de polling limité.
- [ ] Tester validation, rate limiting, token valide/invalide et absence de fuite d’identifiant.

Validation :

```bash
php artisan migrate:fresh --seed
php artisan route:list --path=conseils
php artisan test tests/Feature/Advice/SubmitAdviceRequestTest.php
php artisan test tests/Feature/Advice/PublicAdviceResultTest.php
vendor/bin/pint --test
npm run build
```

Critère de sortie : la soumission retourne vite, crée PENDING, dispatche un Job et redirige vers un token non prédictible.

### Phase 6 : Implémenter le préfiltrage Laravel

Résultat : Laravel produit une liste déterministe de candidates éligibles avant tout conseiller.

- [ ] Implémenter `PlantEligibilityService`.
- [ ] Couvrir activité, stock, environnement, exposition, espace, entretien et sécurité animale.
- [ ] Centraliser les seuils d’espace dans la configuration.
- [ ] Tester les combinaisons limites, les valeurs inconnues et la liste vide.

Validation :

```bash
php artisan test tests/Unit/Plants/PlantEligibilityServiceTest.php
php artisan test --filter=PlantEligibility
vendor/bin/pint --test
```

Critère de sortie : chaque règle BR-01 à BR-07 dispose d’au moins un test positif et négatif.

### Phase 7 : Ajouter le contrat IA et le fake déterministe

Résultat : le parcours asynchrone complet fonctionne sans réseau.

- [ ] Créer les DTO et le contrat `PlantAdvisor`.
- [ ] Lier `FakePlantAdvisor` par configuration.
- [ ] Créer la migration et le modèle des recommandations avec snapshots et contraintes uniques.
- [ ] Implémenter `AdviceResultValidator`.
- [ ] Orchestrer le Job avec transitions, retries bornés, idempotence et transaction finale courte.
- [ ] Produire un résultat COMPLETED vide sans appeler le fake lorsqu’aucune candidate n’existe.
- [ ] Gérer sorties invalides et échecs contrôlés.

Validation :

```bash
php artisan migrate:fresh --seed
php artisan test tests/Unit/Advice
php artisan test tests/Feature/Jobs/GeneratePlantAdviceJobTest.php
php artisan test --filter=AdviceResultValidator
php artisan queue:work --once
vendor/bin/pint --test
```

Critère de sortie : les tests rejettent un ID inventé, une rupture, une plante inactive, un doublon et un replay du Job.

### Phase 8 : Finaliser les résultats et l’historique

Résultat : le visiteur consulte le résultat et le gérant audite les demandes.

- [ ] Afficher les quatre états sur la page publique.
- [ ] Afficher résumé, raisons, entretien, avertissement, snapshot et stock courant.
- [ ] Ajouter tableau de bord, historique filtrable et détail d’administration.
- [ ] Vérifier l’échappement des textes issus du conseiller.
- [ ] Vérifier l’accessibilité clavier et la présentation mobile.

Validation :

```bash
php artisan test tests/Feature/Advice
php artisan test tests/Feature/Admin/AdviceRequests
php artisan test
vendor/bin/pint --test
npm run build
```

Critère de sortie : le parcours catalogue → demande → worker → résultat est démontrable avec le fake.

### Phase 9 : Ajouter la CI et durcir le MVP

Résultat : Docker et GitHub Actions reproduisent les contrôles du projet.

- [ ] Créer le workflow GitHub Actions avec PHP 8.3, MySQL 8.4 et Node.
- [ ] Mettre en cache les dépendances sans masquer les erreurs.
- [ ] Lancer migrations, Pest, Pint et build Vite.
- [ ] Vérifier les logs, secrets, rate limits et messages publics.
- [ ] Documenter installation, création du gérant, worker et démonstration.

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

### Phase 10 : Intégrer Groq après validation du MVP fake

Résultat : `GroqPlantAdvisor` remplace le fake par configuration sans modifier les règles métier.

- [ ] Valider le client ou SDK Groq et sa compatibilité avec Laravel 13.
- [ ] Définir timeouts, retries, structured output et limites de payload.
- [ ] Implémenter le mapping vers les DTO existants.
- [ ] Ajouter des tests HTTP simulés pour succès, timeout, indisponibilité et JSON invalide.
- [ ] Garder `AI_PROVIDER=fake` en développement et dans les tests.
- [ ] Effectuer un test manuel Groq opt-in, hors CI.

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
- Un test passe avec le fake mais masque un appel réseau.
- Un retry laisse une demande bloquée en PROCESSING.
- Une modification de stock entre l’appel IA et la transaction finale invalide une candidate.
- Le polling surcharge l’application ou continue après un état terminal.

## Questions ouvertes non bloquantes pour la phase 1

1. Quelles limites de hauteur et de largeur définissent SMALL, MEDIUM et LARGE ?
2. Le formulaire MVP collecte-t-il le nom et l’email, ou uniquement les contraintes de conseil ?
3. Le service web Docker doit-il utiliser Nginx, comme proposé, ou Laravel Octane/PHP intégré pour la démonstration ?

Le plan retient Nginx et reporte les deux premières réponses à la phase 5 si aucune décision n’intervient avant.
