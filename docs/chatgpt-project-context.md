# Contexte de mise à jour — Pépinière IA

> Document autonome à transmettre à ChatGPT avant de lui demander une modification du projet.
>
> Dernière vérification par rapport au dépôt : 7 août 2026.

## Mode d’emploi

Copier ce fichier dans une nouvelle conversation ChatGPT, puis ajouter à la fin la liste précise des changements souhaités. Demander à ChatGPT de distinguer :

1. ce qui existe actuellement ;
2. ce qui doit changer ;
3. les fichiers et migrations concernés ;
4. les conséquences fonctionnelles, techniques et de sécurité ;
5. les tests à ajouter ou modifier ;
6. les décisions documentaires devenues obsolètes.

Ne fournir aucune valeur de `.env`, clé Groq, mot de passe ou autre secret. Les noms de variables d’environnement documentés ci-dessous sont suffisants.

## Identité du projet

- Nom : **Pépinière IA**.
- Dépôt GitHub : `abit0uh4r/pepeniaireAI`.
- Type : application web monolithique Laravel rendue côté serveur.
- Langue de l’interface et des réponses de conseil : français.
- Devise affichée : dirham marocain, `MAD`.
- Objectif : gérer le catalogue et le stock d’une pépinière, puis conseiller un visiteur à partir des plantes réellement disponibles.

## Utilisateurs et fonctionnalités actuelles

### Visiteur sans compte

Le visiteur peut :

- consulter la page d’accueil ;
- soumettre une demande sur `/conseil` ;
- renseigner son environnement, son exposition, la taille de son espace, sa disponibilité d’entretien et une description libre ;
- fournir facultativement son prénom ;
- recevoir immédiatement une URL de suivi contenant un token public aléatoire ;
- suivre les états `PENDING`, `PROCESSING`, `COMPLETED` ou `FAILED` ;
- consulter les recommandations persistées lorsque le traitement est terminé.

Le formulaire ne collecte aucune adresse email visiteur. Le prénom facultatif n’est jamais transmis au fournisseur IA. Laravel affiche le lien de suivi dès la soumission.

### Gérant authentifié

Le gérant peut :

- se connecter avec Laravel Breeze et une session web ;
- gérer son profil et son mot de passe ;
- créer, modifier, rechercher et filtrer les plantes ;
- gérer le prix en MAD, le stock et l’état actif ;
- désactiver ou archiver une plante ;
- consulter la liste et le détail des demandes de conseil.

L’inscription publique est désactivée. Il n’existe aucune route `/register`. Le compte gérant est créé par `php artisan manager:create` ou par `ManagerSeeder`, à partir des variables `MANAGER_NAME`, `MANAGER_EMAIL` et `MANAGER_PASSWORD`.

## Stack imposée et réellement utilisée

| Élément | Choix actuel |
|---|---|
| Backend | Laravel 13, PHP 8.3 |
| Rendu | Blade côté serveur |
| CSS | Tailwind CSS 3 |
| Interactivité légère | Alpine.js 3 |
| Authentification | Laravel Breeze Blade, guard `web`, sessions |
| Base | MySQL 8.4 |
| Queue | Laravel Queue avec connexion `database` |
| Sessions | fichiers |
| Cache | fichiers |
| Tests | Pest 4 |
| Formatage | Laravel Pint |
| Assets | Vite 7 et Node.js 22 en CI |
| Environnement | Docker Compose, PHP-FPM, Nginx, MySQL et worker |
| CI | GitHub Actions |
| Fournisseur IA | `GroqPlantAdvisor` (unique) |
| Emails locaux | driver Laravel `log` |

Éléments absents et à ne pas réintroduire sans nouvelle décision explicite : React, Vue, Livewire, Inertia, SPA, Redis, Horizon, Sanctum, microservices et API REST produit séparée.

Les réponses JSON de `/health` et du polling de suivi sont des points techniques internes au monolithe, pas une API REST métier.

## Architecture applicative actuelle

L’architecture a volontairement été simplifiée pour le MVP. Les dossiers ou couches `Actions/`, `DTOs/`, `Contracts/` et la classe séparée `AdviceResultValidator` ont été supprimés.

Les briques conservées sont :

```text
app/
├── Console/Commands/       # création du compte gérant
├── Enums/                  # valeurs métier fermées
├── Http/
│   ├── Controllers/        # orchestration HTTP publique, admin et auth
│   ├── Middleware/         # en-têtes de sécurité
│   └── Requests/           # validation des formulaires
├── Jobs/                   # orchestration asynchrone du conseil
├── Models/                 # modèles et relations Eloquent
├── Policies/               # autorisations administratives
├── Providers/              # liaison du fournisseur IA
├── Services/               # PlantEligibilityService et conseillers IA
├── Support/                # formatage monétaire
└── View/Components/        # layouts Blade PHP
```

Responsabilités principales :

- les contrôleurs gèrent HTTP, autorisations, redirections et vues ;
- les Form Requests valident et normalisent les entrées ;
- `PlantEligibilityService` applique le préfiltrage déterministe ;
- `PlantAdvisor` est l’interface commune aux conseillers ;
- `GroqPlantAdvisor` appelle Groq et exige une sortie JSON structurée en français ;
- `GeneratePlantAdviceJob` orchestre le traitement, valide la réponse IA et persiste le résultat ;
- Eloquent et MySQL restent les sources de vérité.

## Principe métier fondamental

L’IA n’est jamais autorisée à décider seule de l’éligibilité ni à modifier les données métier.

```text
formulaire validé
    → création de la demande PENDING
    → dispatch du Job
    → claim atomique PROCESSING
    → préfiltrage Laravel
    → classement et explications par PlantAdvisor
    → validation de la réponse par Laravel dans le Job
    → relecture et verrouillage SQL
    → seconde vérification d’éligibilité et de stock
    → persistance transactionnelle
    → COMPLETED ou FAILED
```

Laravel :

- sélectionne les candidates ;
- transmet uniquement ces candidates au conseiller ;
- vérifie les identifiants renvoyés ;
- élimine les doublons et résultats invalides ;
- contrôle la limite de recommandations ;
- revalide activité, critères et stock avant écriture ;
- persiste uniquement les recommandations valides.

L’IA :

- classe les candidates fournies ;
- rédige un résumé, des conseils généraux et une justification en français.

L’IA ne peut pas :

- créer ou inventer une plante ;
- recommander un identifiant non candidat ;
- lire ou écrire directement dans MySQL ;
- modifier le stock ou le prix ;
- rendre éligible une plante inactive, épuisée ou incompatible ;
- inventer des propriétés botaniques absentes du catalogue.

## Règles de préfiltrage actuelles

Une plante est éligible seulement si :

- elle n’est pas archivée ;
- elle est active ;
- son stock est strictement positif ;
- son exposition unique correspond à celle de la demande ;
- son environnement correspond, ou la plante possède l’environnement `BOTH` ;
- ses hauteur et largeur adultes sont renseignées et respectent les limites de l’espace ;
- son niveau d’entretien ne dépasse pas la disponibilité du visiteur.

Limites actuelles dans `config/advice.php` :

| Taille | Hauteur maximale | Largeur maximale |
|---|---:|---:|
| `SMALL` | 60 cm | 45 cm |
| `MEDIUM` | 120 cm | 80 cm |
| `LARGE` | 240 cm | 160 cm |

Le nombre maximal de recommandations est configuré avec `ADVICE_MAX_RECOMMENDATIONS`, avec une valeur par défaut de 3.

## Conseillers IA

### Groq, fournisseur unique

`GroqPlantAdvisor` est le fournisseur actif (`AI_PROVIDER=groq`). Sa configuration repose sur :

- `GROQ_API_KEY` ;
- `GROQ_BASE_URL` ;
- `GROQ_MODEL` ;
- `GROQ_TIMEOUT` ;
- `GROQ_MAX_TOKENS`.

La clé reste uniquement dans `.env` ou dans l’environnement d’exécution. Elle ne doit jamais apparaître dans un document, un commit, un test ou une capture.

Groq reçoit le contexte utile et les candidates préfiltrées, sans nom, email, prix ni quantité de stock. Le prompt exige du français et une structure JSON contenant :

- `space_summary` ;
- `general_advice` ;
- `recommendations[]` avec `plant_id`, `rank` et `reason`.

Même avec un schéma JSON strict côté Groq, le Job ne fait jamais confiance à la sortie externe.

## Validation et gestion des erreurs

La validation de la réponse IA se trouve directement dans `GeneratePlantAdviceJob`, conformément à la simplification MVP.

Le Job contrôle notamment :

- les types des champs ;
- l’appartenance des identifiants à la liste candidate ;
- l’absence de doublon de plante ;
- les rangs et la limite de résultats ;
- la présence d’une justification ;
- la longueur maximale des textes persistés.

Les deux catégories métier d’échec sont :

- `NO_ELIGIBLE_PLANTS` : aucune candidate après préfiltrage ;
- `AI_ERROR` : fournisseur indisponible ou résultat inexploitable.

La base ne possède plus de colonne `failure_code`. Seul `failure_message` est persisté. La table technique `failed_jobs` reste utilisée par Laravel Queue pour les exceptions de jobs ayant épuisé leurs tentatives.

## Modèle de données actuel

### `users`

Compte du gérant : nom, email unique, vérification, mot de passe haché et remember token.

### `plants`

Champs fonctionnels principaux :

- nom, espèce et description ;
- environnement ;
- exposition unique stockée en `ENUM` SQL ;
- niveaux d’arrosage et d’entretien ;
- hauteur et largeur adultes ;
- prix `DECIMAL(10,2)` affiché en MAD ;
- quantité en stock ;
- état actif ;
- suppression logique.

Le champ `pet_safe` a été supprimé. L’exposition n’est plus un JSON multiple.

### `advice_requests`

Contient : token public, prénom facultatif, critères du visiteur, texte libre, statut, résumé, conseils, message d’échec et horodatages de traitement. La colonne `customer_email` a été supprimée.

Les champs `has_pets`, `failure_code` et `raw_ai_response` ont été supprimés.

### `plant_recommendations`

Associe une demande et une plante avec le rang, la justification et `stock_quantity_snapshot`.

Le snapshot de quantité est conservé. Le snapshot de prix a été supprimé. Le Job ne décrémente jamais le stock lorsqu’il crée une recommandation.

### `jobs` et `failed_jobs`

Tables techniques de la queue Laravel avec le driver `database`.

## Routes utiles

### Publiques

- `GET /` : accueil ;
- `GET /health` : santé JSON ;
- `GET /conseil` : formulaire ;
- `POST /conseil` : création limitée à 10 requêtes/minute ;
- `GET /conseil/suivi/{token}` : page de suivi ;
- `GET /conseil/suivi/{token}/status` : statut JSON de polling.

Le token public contient 32 octets aléatoires encodés en 64 caractères hexadécimaux. L’identifiant SQL n’est pas exposé dans l’URL.

### Authentification et administration

- `GET/POST /login` et `POST /logout` ;
- récupération et réinitialisation du mot de passe ;
- vérification d’email ;
- `/admin` : tableau d’entrée simple, sans statistiques métier ;
- `/admin/plants` : gestion du catalogue et du stock ;
- `/admin/advice-requests` : historique simple des demandes ;
- `/profile` : gestion du compte.

Les routes administratives exigent `auth` et `verified`. Le profil exige `auth`.

## Sécurité actuelle

- cookies de session et protection CSRF du middleware `web` ;
- aucune inscription publique ;
- policies pour les ressources administratives ;
- limitation de débit sur la soumission et le suivi ;
- token public aléatoire non dérivé de l’identifiant SQL ;
- statut public minimal avec `Cache-Control: private, no-store` ;
- échappement Blade des textes IA ;
- middleware d’en-têtes de sécurité ;
- secrets exclus de Git par `.gitignore` ;
- détection de motifs de clés dans la CI ;
- aucun appel réseau Groq pendant les tests.

## Docker et exécution

`compose.yaml` définit quatre services :

| Service | Rôle |
|---|---|
| `web` | Nginx, port hôte `8088` par défaut |
| `app` | Laravel sous PHP-FPM 8.3 |
| `mysql` | MySQL 8.4, port hôte `33060` |
| `queue-worker` | même image que `app`, exécute `queue:work` |

Le worker utilise `--tries=3`, `--timeout=90` et redémarre périodiquement avec `--max-time=3600`.

Mailpit a été retiré. Le driver mail actuel est `log`. Redis et Horizon ne sont pas présents.

## Tests et intégration continue

La suite Pest couvre notamment :

- authentification, absence d’inscription et accès admin ;
- création du gérant et seeders ;
- catalogue, stock, validation et autorisations ;
- formulaire public, token et suivi ;
- règles de préfiltrage ;
- fournisseur Groq simulé avec `Http::fake()` ;
- traitement du Job, erreurs, déduplication et concurrence ;
- schéma de base simplifié ;
- formatage monétaire en MAD ;
- route de santé et en-têtes de sécurité.

La GitHub Action `.github/workflows/ci.yml` utilise PHP 8.3, Node.js 22 et MySQL 8.4. Elle exécute :

```bash
composer install
npm ci
npm run build
composer validate --strict
composer audit --no-interaction --locked
php artisan migrate:fresh --seed --force
php artisan test
vendor/bin/pint --test
```

Elle vérifie aussi que `.env` n’est pas suivi et recherche des motifs ressemblant à des clés secrètes.

## Fonctionnalités explicitement reportées après le MVP

- vente en ligne, panier, commande et paiement ;
- décrément ou réservation automatique du stock ;
- snapshot du prix ;
- comparaison entre stock courant et snapshot ;
- filtre par période dans l’historique ;
- relance manuelle d’une demande échouée ;
- conservation de la réponse IA brute ;
- critère `pet_safe` et règle associée ;
- tableau de bord statistique ;
- envoi automatique du lien de suivi par email ;
- API REST séparée et documentation Scribe.

Le prix et le stock sont malgré tout utiles au MVP : le prix présente l’offre commerciale et le stock empêche de conseiller une plante indisponible. Ils ne constituent pas encore un système de vente.

## Fichiers essentiels à examiner avant une modification

| Sujet | Fichiers principaux |
|---|---|
| Routes | `routes/web.php`, `routes/auth.php` |
| Configuration conseil/IA | `config/advice.php`, `.env.example` |
| Liaison IA | `app/Providers/AppServiceProvider.php` |
| Conseil asynchrone | `app/Jobs/GeneratePlantAdviceJob.php` |
| Préfiltrage | `app/Services/PlantEligibilityService.php` |
| Fournisseur | `app/Services/PlantAdvisor.php`, `GroqPlantAdvisor.php` |
| Domaine | `app/Models/`, `app/Enums/` |
| Validation HTTP | `app/Http/Requests/` |
| Pages | `resources/views/` |
| Schéma | `database/migrations/` |
| Démonstration | `database/seeders/PlantSeeder.php` |
| Docker | `compose.yaml`, `docker/` |
| CI | `.github/workflows/ci.yml` |
| Tests | `tests/Feature/`, `tests/Unit/` |
| Décisions | `docs/technical-decisions.md` |

## Écarts documentaires à connaître

Le dépôt contient encore des formulations historiques dans `AGENTS.md` et possiblement dans le cahier des charges initial. Lorsqu’elles contredisent les décisions plus récentes et le code actuel, ne pas les réintroduire automatiquement.

Les décisions actuelles sont :

- pas de Mailpit ; emails dans les logs ;
- pas de dossiers `Actions`, `DTOs` ou `Contracts` ;
- `PlantAdvisor` reste une interface mais se trouve dans `app/Services` ;
- validation IA directement dans le Job ;
- exposition simple, pas JSON ;
- pas de `pet_safe` ni de règle animaux ;
- pas de `failure_code`, seulement `failure_message` ;
- pas de conservation de `raw_ai_response` ;
- snapshot de quantité conservé, snapshot de prix supprimé ;
- seulement deux catégories d’échec métier ;
- pas de tableau de bord statistique ;
- pas de Scribe ni d’API REST séparée.

Toute mise à jour future doit soit conserver cette base, soit présenter explicitement la décision qui la remplace et mettre à jour ensemble le code, les tests, `.env.example`, le README et la documentation concernée.

## Règles de contribution Git

- ne pas travailler directement sur `main` ;
- une modification cohérente par branche dédiée (`feature/`, `fix/`, `test/`, `refactor/`, `docs/` ou `chore/`) ;
- partir d’un working tree propre et d’un `main` actualisé avec `git pull --ff-only origin main` ;
- utiliser les Conventional Commits ;
- ne jamais utiliser `git push --force` ;
- ne jamais ignorer un test en échec ;
- contrôler le diff et l’absence de secret avant commit ;
- pousser la branche après validation, sans fusion automatique.

Le porteur du projet a demandé de pousser directement la branche sans créer de Pull Request, sauf instruction contraire ultérieure.

## Critères de validation d’une évolution

Selon les fichiers touchés, exécuter :

```bash
composer validate --strict
vendor/bin/pint --test
php artisan test
npm run build
php artisan migrate:fresh --seed
docker compose config
```

Si les migrations changent, elles doivent fonctionner sur une base vide MySQL 8.4 et rester réversibles. Si l’IA change, les tests doivent rester sans réseau et utiliser `Http::fake()`.

## Consigne prête à joindre à une demande ChatGPT

Utiliser le texte suivant après avoir fourni ce document :

> Voici l’état actuel et vérifié de mon projet Pépinière IA. Je vais maintenant te donner des changements souhaités. Analyse leurs impacts sans supposer que les éléments reportés sont déjà implémentés. Propose d’abord les décisions, fichiers, migrations, tests et risques concernés. Respecte le principe selon lequel Laravel et MySQL sont la source de vérité : l’IA classe uniquement des candidates préfiltrées et ne modifie jamais le catalogue, le prix ou le stock. Ne génère ni secret ni valeur de `.env`. Signale toute contradiction avec l’état actuel avant de proposer du code.

### Changements souhaités

À compléter avant l’envoi :

```text
- Changement 1 :
- Changement 2 :
- Changement 3 :
```
