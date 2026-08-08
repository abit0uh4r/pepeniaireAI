# Architecture et flux d’exécution

## Vue d’ensemble

```text
Navigateur
   |
   | HTTP : pages Blade, formulaires, statut JSON
   v
Nginx :8088
   |
   | FastCGI
   v
Laravel / PHP-FPM 8.3
   |               |
   | SQL           | insertion d'un job
   v               v
MySQL 8.4 <----- queue-worker Laravel
                       |
                       | fournisseur Groq unique
                       `--> GroqPlantAdvisor --> API Groq
```

`app` et `queue-worker` utilisent la même image Docker. Le premier répond aux requêtes web ; le second exécute `php artisan queue:work`. MySQL stocke le domaine et la file de tâches. Nginx expose uniquement le dossier `public/`.

## Organisation en couches

### Couche HTTP

Les routes dans `routes/web.php` associent une URL à un contrôleur. Les contrôleurs choisissent la vue ou la redirection, déclenchent les autorisations et délèguent la validation aux Form Requests.

Les routes d’authentification de Breeze vivent dans `routes/auth.php`. Elles utilisent le middleware `web`, donc les cookies de session et la protection CSRF s’appliquent. L’absence de routes `register` désactive l’inscription.

### Couche de validation et d’autorisation

Les classes de `app/Http/Requests` valident les données avant leur arrivée dans le contrôleur. Les policies de `app/Policies` protègent les plantes et les demandes administratives. Les groupes de routes ajoutent aussi `auth` et `verified`.

La validation HTTP répond à la question « les données reçues ont-elles une forme acceptable ? ». `PlantEligibilityService` répond à une autre question : « cette plante respecte-t-elle les règles déterministes pour cette demande ? ».

### Couche métier

Les modèles Eloquent représentent les quatre entités persistées. Les enums donnent un type PHP aux valeurs fermées. `PlantEligibilityService` centralise le préfiltrage. L’interface `PlantAdvisor` isole le Job du fournisseur choisi.

Le projet a supprimé les dossiers `Actions`, `DTOs` et `Contracts` envisagés au départ. Pour ce MVP, les contrôleurs, Requests, modèles, services et Job suffisent. `PlantAdvisor` reste une interface, mais elle réside directement dans `app/Services` avec ses implémentations.

### Couche asynchrone

`GeneratePlantAdviceJob` orchestre tout le traitement différé. Il possède trois tentatives, un timeout de 90 secondes et des délais de reprise de 10 puis 30 secondes. Il reçoit seulement l’identifiant de la demande pour sérialiser un message stable.

Le Job contient la validation de la réponse IA. Ce placement garde dans un seul fichier les règles propres à l’orchestration : contrat de sortie, relecture des candidates, transaction et changement de statut.

### Couche de présentation

Blade produit le HTML. Trois layouts séparent l’espace public, l’authentification et l’administration. Les composants Blade factorisent les champs, boutons, statuts, menus et modales. Tailwind fournit les classes CSS. Alpine.js gère seulement les interactions locales.

## Flux de la demande publique

```text
GET /conseil
  -> formulaire Blade

POST /conseil
  -> StoreAdviceRequest
  -> AdviceRequest::create(PENDING + token)
  -> GeneratePlantAdviceJob::dispatch(id)
  -> redirection vers /conseil/suivi/{token}

queue-worker
  -> claim atomique PENDING -> PROCESSING
  -> PlantEligibilityService
  -> PlantAdvisor
  -> validation de la sortie
  -> transaction et verrous SQL
  -> recommandations + snapshot de quantité
  -> COMPLETED

page de suivi
  -> GET /conseil/suivi/{token}/status
  -> recharge lorsque terminal=true
```

Le token contient 32 octets aléatoires encodés en hexadécimal. La contrainte de route accepte exactement 64 caractères `[a-f0-9]`. Le contrôleur cherche la ligne par `public_token` et retourne 404 lorsque le token n’existe pas.

Le point `/status` retourne seulement `status`, `label`, `terminal` et `updated_at`. Il n’expose ni coordonnées, ni texte libre, ni identifiant SQL. L’en-tête `Cache-Control: private, no-store` évite la conservation du statut par un cache partagé.

## Préfiltrage

`PlantEligibilityService::eligiblePlants()` commence par une requête SQL : plante active, stock positif et exposition égale. Il applique ensuite en PHP les règles qui utilisent les enums et les seuils :

```text
active
AND stock_quantity > 0
AND exposure = demande.exposure
AND (environment = demande.environment OR environment = BOTH)
AND adult_height_cm <= limite hauteur
AND adult_width_cm <= limite largeur
AND maintenance_level <= disponibilité d'entretien
```

Le tri par identifiant rend le résultat stable pour Groq et pour les tests. Si la configuration des limites manque ou contient un type inattendu, le service refuse la plante. Ce comportement « fermé » évite d’élargir les recommandations lors d’une erreur de configuration.

## Contrat avec les conseillers

`PlantAdvisor::advise()` reçoit :

- un tableau de contexte avec environnement, exposition, taille, entretien et description libre ;
- une collection Eloquent qui contient seulement les plantes candidates.

Il retourne un tableau avec `space_summary`, `general_advice` et `recommendations`. Chaque recommandation contient `plant_id`, `rank` et `reason`.

### Groq, fournisseur unique

`GroqPlantAdvisor` est l’unique implémentation active de `PlantAdvisor`. Il utilise le client HTTP de Laravel et le endpoint `/chat/completions`. Il demande une réponse conforme à un schéma JSON strict et exige des textes français. Il transmet les propriétés botaniques nécessaires, sans prix, stock, nom du visiteur ni email.

Le schéma demandé à Groq améliore la régularité de la sortie. Il ne remplace pas les contrôles Laravel, car un fournisseur externe peut échouer, changer ou renvoyer une valeur pourtant bien formée mais interdite.

## Validation défensive et transaction

Le Job vérifie que les champs de premier niveau ont le bon type. Pour chaque recommandation, il contrôle :

- le type de `plant_id`, `rank` et `reason` ;
- l’appartenance de l’identifiant aux candidates ;
- l’absence de doublon ;
- un rang entre 1 et la limite configurée ;
- une raison non vide ;
- la longueur maximale des textes persistés.

Après l’appel externe, une transaction recharge la demande et les plantes avec `lockForUpdate()`. Le service d’éligibilité vérifie encore les données. Cette seconde lecture couvre un changement de stock ou une désactivation survenus pendant l’appel IA.

La transaction n’entoure pas l’appel Groq. Elle garde donc les verrous SQL pendant quelques millisecondes au lieu de les conserver durant une requête réseau.

## États et concurrence

```text
PENDING --claim atomique--> PROCESSING --succès--> COMPLETED
                                  |
                                  +-- aucune candidate --> FAILED
                                  `-- échec terminal IA -> FAILED
```

La mise à jour conditionnelle du claim garantit qu’un seul worker transforme une demande `PENDING`. Un Job qui trouve une demande terminale s’arrête. La contrainte unique sur `(advice_request_id, plant_id)` empêche deux recommandations de la même plante pour une demande.

Le snapshot `stock_quantity_snapshot` conserve la quantité observée au moment du résultat. Il sert à l’audit ; le Job ne décrémente jamais le stock.

## Modèle de données

### `users`

Compte gérant : nom, email unique, date de vérification, mot de passe haché et remember token. L’application ne distingue pas encore plusieurs rôles ; tout utilisateur vérifié possède les droits de gestion définis par les policies.

### `plants`

Catalogue : nom, espèce, description, environnement, exposition, niveaux d’arrosage et d’entretien, dimensions, prix, quantité, activité et suppression logique.

Les enums PHP convertissent les chaînes SQL en objets typés. Le prix utilise `DECIMAL(10,2)` et s’affiche en MAD. La suppression logique conserve la ligne pour l’historique.

### `advice_requests`

Entrée du visiteur et résultat global : token public, prénom facultatif, critères, description, statut, résumé, conseils, message d’échec et horodatages de traitement. La table ne conserve aucune adresse email visiteur.

### `plant_recommendations`

Association entre une demande et une plante : rang, justification et snapshot de quantité. Les clés étrangères utilisent une suppression restreinte. La relation vers `Plant` inclut les plantes archivées avec `withTrashed()` afin que l’historique reste lisible.

### `jobs` et `failed_jobs`

Tables techniques de Laravel Queue. `jobs` contient les messages en attente ou réservés. `failed_jobs` conserve les tâches qui ont épuisé leurs tentatives avec leur exception technique.

## Authentification et sécurité

- Breeze utilise le guard `web` et un cookie de session fichier.
- Les formulaires Blade incluent un token CSRF.
- Les routes d’administration exigent `auth` et, sauf le profil, `verified`.
- Les policies autorisent les opérations administratives aux utilisateurs authentifiés et vérifiés.
- Le catalogue courant exclut les lignes archivées ; `/admin/plants/archived` les expose au gérant et `restore` enlève l’archive sans réactiver la fiche.
- Les routes publiques sensibles ont un rate limit.
- Blade échappe les textes avec `{{ ... }}`, y compris les textes du conseiller.
- `SecurityHeaders` ajoute les protections de type MIME, framing, referrer et permissions navigateur.
- `.env` reste ignoré ; `.env.example` contient uniquement des emplacements vides.
- La CI cherche des motifs de clés secrètes connues dans les fichiers suivis.

## Docker et réseau local

| Service | Image ou construction | Port hôte | Responsabilité |
|---|---|---:|---|
| `web` | Dockerfile Nginx | 8088 | Reçoit HTTP et transmet PHP à `app:9000`. |
| `app` | Dockerfile PHP | aucun | PHP-FPM, Artisan et code Laravel. |
| `mysql` | `mysql:8.4.10` | 33060 | Base métier et queue. |
| `queue-worker` | même image que `app` | aucun | Consomme la queue `database`. |

Les volumes `mysql-data` et `laravel-storage` conservent respectivement la base et les fichiers Laravel. Le build multi-stage compile les assets avec Node, installe les dépendances Composer, puis copie les résultats dans l’image PHP finale.

Le driver mail est `log`. Aucun service Mailpit ne tourne dans la composition actuelle.

## CI

À chaque push et Pull Request, GitHub Actions :

1. démarre MySQL 8.4 ;
2. vérifie l’hygiène Git et l’absence de `.env` ou de clé probable ;
3. installe PHP 8.3, Composer, Node 22 et les dépendances ;
4. construit les assets ;
5. valide `composer.json` et audite les dépendances ;
6. recrée et remplit la base ;
7. exécute Pest ;
8. exécute Pint en mode contrôle.

Les tests utilisent une queue synchrone et n’appellent pas Groq.
