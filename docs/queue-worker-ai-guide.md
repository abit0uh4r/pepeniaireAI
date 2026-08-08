# Queue, worker, Job et appel à l’IA

## Objectif de ce document

Ce document explique, depuis le début, comment une demande de conseil passe du
formulaire public jusqu’au résultat affiché au visiteur.

Il s’adresse à une personne qui découvre Laravel et qui ne connaît pas encore
les mots **queue**, **worker**, **Job**, **provider** ou **appel IA**.

Le parcours décrit ici est celui de Pépinière IA :

```text
Formulaire public
      |
      v
Laravel valide la demande
      |
      v
Création d'une demande PENDING
      |
      v
Dispatch de GeneratePlantAdviceJob
      |
      v
Ligne ajoutée dans la table jobs
      |
      v
Le queue-worker prend la ligne
      |
      v
Le Job passe la demande à PROCESSING
      |
      v
PlantEligibilityService filtre le catalogue
      |
      v
PlantAdvisor appelle le fournisseur choisi
      |
      v
Le Job contrôle la réponse IA
      |
      v
Transaction MySQL : recommandations valides uniquement
      |
      v
Demande COMPLETED ou FAILED
      |
      v
La page de suivi se met à jour avec Alpine.js
```

L’idée essentielle est la suivante : **l’IA propose, mais Laravel décide**.
L’IA ne lit pas directement la base, ne modifie pas le stock et ne peut pas
imposer une plante qui n’a pas été sélectionnée par Laravel.

---

## 1. Le problème que la queue résout

Une requête web est normalement courte : le navigateur envoie un formulaire et
attend une réponse HTTP. Si Laravel appelait Groq pendant cette même requête,
le visiteur devrait attendre la fin de l’appel réseau, du traitement du modèle
et du contrôle de la réponse.

Cela créerait plusieurs problèmes :

- la page pourrait sembler bloquée pendant plusieurs secondes ;
- un ralentissement de Groq bloquerait la requête du visiteur ;
- un dépassement de délai HTTP pourrait interrompre le traitement ;
- il serait difficile de réessayer automatiquement un appel temporairement en
  échec.

La **queue** permet de séparer deux moments :

1. répondre rapidement au navigateur en disant « votre demande est reçue » ;
2. traiter la demande en arrière-plan, après la réponse HTTP.

Une comparaison simple :

| Élément informatique | Comparaison dans une pépinière |
| --- | --- |
| Queue | Une file de bons de travail |
| Job | Un bon qui décrit une tâche précise |
| Worker | La personne qui prend le prochain bon |
| Fournisseur IA | Le spécialiste consulté par le worker |
| Table `jobs` | Le meuble où les bons sont rangés |
| Table `failed_jobs` | Le registre des bons qui ont définitivement échoué |

La queue n’est donc pas l’IA. Elle est le mécanisme qui programme et exécute
le travail plus tard.

---

## 2. Les acteurs du parcours

Voici les fichiers importants et leur rôle.

| Fichier | Rôle en langage simple |
| --- | --- |
| [`routes/web.php`](../routes/web.php) | Déclare les adresses HTTP du formulaire et du suivi |
| [`StoreAdviceRequest.php`](../app/Http/Requests/Public/StoreAdviceRequest.php) | Vérifie les champs envoyés par le visiteur |
| [`AdviceRequestController.php`](../app/Http/Controllers/Public/AdviceRequestController.php) | Crée la demande, programme le Job et renvoie la page suivante |
| [`AdviceRequest.php`](../app/Models/AdviceRequest.php) | Représente une demande enregistrée en base |
| [`GeneratePlantAdviceJob.php`](../app/Jobs/GeneratePlantAdviceJob.php) | Ordonne tout le traitement asynchrone |
| [`PlantEligibilityService.php`](../app/Services/PlantEligibilityService.php) | Applique les filtres déterministes au catalogue |
| [`PlantAdvisor.php`](../app/Services/PlantAdvisor.php) | Définit le contrat commun d’un conseiller |
| [`FakePlantAdvisor.php`](../app/Services/FakePlantAdvisor.php) | Réponse locale déterministe, sans réseau |
| [`GroqPlantAdvisor.php`](../app/Services/GroqPlantAdvisor.php) | Appelle l’API Groq avec HTTP |
| [`AppServiceProvider.php`](../app/Providers/AppServiceProvider.php) | Choisit l’implémentation de `PlantAdvisor` |
| [`config/advice.php`](../config/advice.php) | Centralise le fournisseur, le modèle et les limites IA |
| [`compose.yaml`](../compose.yaml) | Démarre l’application, MySQL et le worker |
| [`track.blade.php`](../resources/views/advice/track.blade.php) | Affiche l’état et interroge périodiquement Laravel |

---

## 3. Étape 1 : le visiteur envoie le formulaire

Le visiteur ouvre `GET /conseil`, remplit les critères puis envoie le formulaire
avec `POST /conseil`.

La route est déclarée dans [`routes/web.php`](../routes/web.php) :

```php
Route::post('/conseil', [AdviceRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('advice.store');
```

Le middleware `throttle:10,1` limite le nombre de soumissions à dix par minute
pour une même origine identifiée par Laravel. Cette limite évite qu’un robot
ne crée un grand nombre de demandes.

Le formulaire utilise aussi la protection CSRF de Laravel. Le jeton CSRF
prouve que l’envoi vient bien d’une page de l’application et non d’un site
tiers qui tenterait de soumettre le formulaire à la place du visiteur.

### Validation de la requête

Le contrôleur reçoit un [`StoreAdviceRequest`](../app/Http/Requests/Public/StoreAdviceRequest.php)
plutôt que de faire lui-même tous les contrôles.

Cette classe vérifie notamment :

- l’environnement demandé ;
- l’exposition ;
- la taille de l’espace ;
- la disponibilité pour l’entretien ;
- la description libre, avec ses limites de longueur ;
- le consentement obligatoire ;
- le format de l’email lorsqu’il est fourni.

Si un champ est incorrect, Laravel renvoie le formulaire avec les erreurs. Le
Job n’est pas créé dans ce cas : une queue ne doit recevoir que des données
validées.

---

## 4. Étape 2 : création de la demande et du token

Après validation, la méthode `store()` du contrôleur fait trois choses :

```php
$data = $request->validated();
unset($data['consent']);

$adviceRequest = AdviceRequest::query()->create($data);
GeneratePlantAdviceJob::dispatch($adviceRequest->getKey());

return to_route('advice.track', ['token' => $adviceRequest->public_token]);
```

### Pourquoi enlever `consent` ?

Le consentement sert à autoriser l’envoi du formulaire. Ce n’est pas une
colonne métier de la demande dans le schéma actuel, donc il est validé puis
retiré avant l’insertion.

### Quel est le statut initial ?

Le modèle [`AdviceRequest`](../app/Models/AdviceRequest.php) possède un hook
`creating`. Juste avant l’insertion, il :

1. génère le token public si nécessaire ;
2. place la demande à `PENDING` si aucun statut n’a été donné.

Le token est généré par :

```php
bin2hex(random_bytes(32));
```

`random_bytes(32)` produit 32 octets aléatoires. Leur représentation
hexadécimale contient 64 caractères. Ce token est indépendant de l’identifiant
SQL de la demande : connaître l’URL d’une demande ne permet donc pas de
deviner l’URL suivante.

Le visiteur est immédiatement redirigé vers une URL de suivi de la forme :

```text
/conseil/suivi/jeton-de-64-caracteres
```

L’identifiant numérique de la demande n’est pas placé dans cette URL publique.

---

## 5. Étape 3 : `dispatch()` et la table `jobs`

La ligne suivante programme le travail :

```php
GeneratePlantAdviceJob::dispatch($adviceRequest->getKey());
```

`dispatch()` ne signifie pas « exécuter immédiatement le code ». Avec
`QUEUE_CONNECTION=database`, Laravel sérialise le Job et l’enregistre dans la
table `jobs` de MySQL.

Le Job transporte uniquement l’identifiant entier de la demande. C’est un
choix important :

- le payload reste petit ;
- il n’emporte pas de modèle devenu obsolète ;
- les données sont relues depuis MySQL au moment du traitement ;
- le nom et l’email ne sont pas copiés dans le Job.

La configuration vient de [`config/queue.php`](../config/queue.php) :

```php
'default' => env('QUEUE_CONNECTION', 'database'),
```

et du fichier `.env` :

```dotenv
QUEUE_CONNECTION=database
```

### La table `jobs`

La migration [`0001_01_01_000002_create_jobs_table.php`](../database/migrations/0001_01_01_000002_create_jobs_table.php)
crée notamment :

- `queue` : le nom de la file, généralement `default` ;
- `payload` : le Job sérialisé ;
- `attempts` : le nombre de tentatives ;
- `reserved_at` : le moment où un worker réserve la ligne ;
- `available_at` : le moment où elle peut être prise ;
- `created_at` : le moment de création.

Cette même migration crée `failed_jobs`, qui conserve les jobs que Laravel n’a
pas réussi à terminer après toutes leurs tentatives.

Le projet n’utilise pas Redis ni Horizon pour cette queue. MySQL est le stockage
de la file, et un processus Laravel joue le rôle de worker.

---

## 6. Étape 4 : le queue-worker prend le Job

Un Job enregistré dans `jobs` ne s’exécute pas tout seul. Il faut un processus
qui surveille cette table : le **worker**.

Dans [`compose.yaml`](../compose.yaml), le service `queue-worker` utilise la
même image PHP que `app` :

```yaml
queue-worker:
  image: pepiniereia-app:local
  command:
    [
      "php", "artisan", "queue:work",
      "--sleep=3",
      "--tries=3",
      "--timeout=90",
      "--max-time=3600"
    ]
```

La commande `php artisan queue:work` boucle de la manière suivante :

1. chercher un Job disponible dans `jobs` ;
2. réserver sa ligne pour éviter qu’un autre worker ne la prenne au même
   moment ;
3. désérialiser le Job ;
4. appeler sa méthode `handle()` ;
5. supprimer la ligne si tout réussit ;
6. augmenter le compteur de tentative ou déplacer le Job vers `failed_jobs`
   s’il échoue définitivement ;
7. attendre quelques secondes s’il n’y a rien à faire, selon `--sleep=3`.

Le worker n’est donc pas une nouvelle application. C’est la même application
Laravel, lancée avec une commande différente.

### Pourquoi une image identique à `app` ?

Le conteneur `app` contient le code PHP, les dépendances Composer et la
configuration. Le `queue-worker` doit voir exactement le même code et les
mêmes variables d’environnement. Utiliser la même image évite qu’une version
du Job soit différente entre le serveur web et le worker.

### Démarrage local

```bash
docker compose up -d
docker compose ps
docker compose logs -f queue-worker
```

Le worker reste actif tant que le service Docker fonctionne. Pour exécuter un
seul Job puis revenir au terminal, on peut lancer ponctuellement :

```bash
docker compose exec app php artisan queue:work --once
```

La commande `--once` est pratique pour comprendre ou diagnostiquer un cas
particulier. En développement normal, le service `queue-worker` travaille en
continu.

---

## 7. Étape 5 : le Job commence son travail

Le fichier [`GeneratePlantAdviceJob`](../app/Jobs/GeneratePlantAdviceJob.php)
implémente `ShouldQueue`. C’est ce marqueur qui indique à Laravel que la classe
est conçue pour être mise en queue.

Son constructeur est volontairement simple :

```php
public function __construct(public int $adviceRequestId) {}
```

La méthode principale est `handle()` :

```php
public function handle(
    PlantAdvisor $plantAdvisor,
    PlantEligibilityService $eligibilityService,
): void
```

Laravel injecte automatiquement les deux services. Le Job n’a donc pas besoin
de construire lui-même ses dépendances.

### 7.1 Relecture de la demande

Le Job recharge la demande avec son identifiant. Si elle n’existe plus ou si
elle possède déjà un statut final (`COMPLETED` ou `FAILED`), le Job s’arrête.

Cela rend le traitement **idempotent** : relancer accidentellement un Job déjà
terminé ne crée pas une deuxième série de recommandations.

### 7.2 Réservation logique de la demande

Si la demande est `PENDING`, le Job la passe atomiquement à `PROCESSING` et
enregistre `processing_started_at`.

Cette étape sert à signaler qu’un worker est déjà en train de la traiter. Si un
deuxième Job identique arrive, il ne doit pas effectuer le même travail en
parallèle.

Les statuts possibles sont définis dans
[`AdviceRequestStatus.php`](../app/Enums/AdviceRequestStatus.php) :

```text
PENDING → PROCESSING → COMPLETED
                    ↘ FAILED
```

`COMPLETED` et `FAILED` sont des états terminaux dans le MVP.

---

## 8. Étape 6 : Laravel préfiltre le catalogue

Avant de parler à l’IA, le Job appelle
[`PlantEligibilityService`](../app/Services/PlantEligibilityService.php).

Ce service applique des règles que Laravel peut vérifier sans intelligence
artificielle :

1. la plante est active ;
2. son stock est supérieur à zéro ;
3. son exposition correspond à la demande ;
4. son environnement correspond, ou la plante est compatible avec les deux ;
5. ses dimensions adultes tiennent dans l’espace demandé ;
6. son niveau d’entretien ne dépasse pas la disponibilité du visiteur.

Le service commence par une requête SQL avec `active()`, `inStock()` et
l’exposition, puis vérifie les règles restantes dans `isEligible()`.

### Pourquoi filtrer avant l’IA ?

Le catalogue de la pépinière est la source de vérité. Si le catalogue contient
1 000 plantes mais que 4 seulement sont actives, en stock et compatibles,
l’IA ne doit recevoir que ces 4 candidates.

Cela apporte trois garanties :

- l’IA travaille sur un petit ensemble compréhensible ;
- elle ne peut pas recommander une plante inactive ou épuisée par simple
  invention ;
- la décision déterministe reste dans le code Laravel, où elle peut être
  testée.

### Absence de candidate

Si la collection est vide, le Job :

- ne fait aucun appel IA ;
- passe la demande à `FAILED` ;
- conserve le message public
  « Aucune plante ne correspond aux critères indiqués. ».

Les causes applicatives sont limitées à `NO_ELIGIBLE_PLANTS` et `AI_ERROR`,
mais le modèle conserve seulement un `failure_message` lisible.

---

## 9. Étape 7 : l’interface `PlantAdvisor`

Le Job ne dépend pas directement de la classe Groq. Il dépend de l’interface
[`PlantAdvisor`](../app/Services/PlantAdvisor.php) :

```php
interface PlantAdvisor
{
    public function advise(
        array $context,
        Collection $candidatePlants
    ): array;
}
```

Cette interface signifie : « tout conseiller doit accepter le contexte de la
demande et les plantes candidates, puis renvoyer un tableau de résultat ».

Le Job ne sait donc pas si le conseiller est :

- un fake local ;
- Groq ;
- un autre fournisseur ajouté plus tard.

Cette séparation est utile parce que le flux métier reste le même lorsque le
fournisseur change.

### Choix du fournisseur par le conteneur Laravel

[`AppServiceProvider`](../app/Providers/AppServiceProvider.php) associe
`PlantAdvisor::class` à une implémentation selon la configuration :

```php
return match (config('advice.ai_provider')) {
    'fake' => new FakePlantAdvisor(...),
    'groq' => new GroqPlantAdvisor(...),
    default => throw new InvalidArgumentException(...),
};
```

La configuration vient de [`config/advice.php`](../config/advice.php), qui lit
les variables d’environnement une seule fois :

```dotenv
AI_PROVIDER=fake
GROQ_API_KEY=
GROQ_BASE_URL=https://api.groq.com/openai/v1
GROQ_MODEL=openai/gpt-oss-20b
```

**Important pour lire le dépôt actuel :** le défaut local et les tests restent
`fake`, car cela garantit un fonctionnement déterministe sans réseau. Pour
utiliser le chemin Groq, il faut activer explicitement :

```dotenv
AI_PROVIDER=groq
GROQ_API_KEY=la-cle-secrete-locale
```

La clé ne doit jamais être écrite dans Git, dans un fichier Markdown ou dans
un log. Le fichier `.env.example` reste vide sur cette valeur.

---

## 10. Étape 8 : l’appel à Groq

Lorsque `AI_PROVIDER=groq`, Laravel construit
[`GroqPlantAdvisor`](../app/Services/GroqPlantAdvisor.php).

Cette classe utilise le client HTTP Laravel, pas un accès direct à MySQL et pas
un SDK qui aurait le droit d’écrire dans l’application.

### 10.1 Ce qui est envoyé

Le Job construit un contexte limité avec :

- environnement ;
- exposition ;
- taille de l’espace ;
- disponibilité pour l’entretien ;
- description libre.

Le nom et l’adresse email du visiteur ne sont pas inclus dans ce contexte.

La liste envoyée à Groq contient seulement les candidates présélectionnées et
les propriétés utiles à leur comparaison :

- identifiant de base de données ;
- nom et espèce ;
- description ;
- environnement et exposition ;
- arrosage et entretien ;
- hauteur et largeur adultes.

Le prix, le stock courant et les informations personnelles ne sont pas envoyés
au fournisseur dans ce parcours. L’IA explique les choix ; Laravel conserve la
maîtrise des données commerciales et de l’éligibilité.

### 10.2 La requête HTTP

Le fournisseur prépare une requête JSON vers :

```text
POST https://api.groq.com/openai/v1/chat/completions
```

La classe configure notamment :

```php
Http::asJson()
    ->acceptJson()
    ->withToken($this->apiKey)
    ->timeout($this->timeout)
    ->baseUrl($this->baseUrl);
```

`withToken()` ajoute le token Bearer. `timeout()` empêche un appel réseau de
rester bloqué indéfiniment. L’URL, le modèle, le timeout et la limite de tokens
sont tous configurables sans modifier le Job.

### 10.3 La consigne et le format de sortie

Le message système demande une réponse en français et rappelle les règles :

- utiliser uniquement les identifiants fournis ;
- ne pas inventer de plante ;
- ne pas inventer une propriété botanique ;
- renvoyer uniquement un objet JSON conforme au schéma.

Le schéma attendu contient :

```json
{
  "space_summary": "Résumé de l'espace",
  "general_advice": "Conseil général",
  "recommendations": [
    {
      "plant_id": 12,
      "rank": 1,
      "reason": "Explication de la compatibilité"
    }
  ]
}
```

Le fournisseur demande le mode `json_schema` lorsque le modèle le supporte.
Cela réduit les réponses qui mélangent du texte explicatif et du JSON.

### 10.4 Ce que fait `GroqPlantAdvisor` en cas d’erreur

La classe transforme plusieurs problèmes techniques en `RuntimeException` :

- clé absente ;
- réponse HTTP refusée ;
- contenu vide ;
- JSON invalide ;
- réponse qui n’est pas un objet JSON.

Elle ne recopie pas le message secret ou le payload complet de Groq dans le
message public. Le Job enregistrera seulement le message applicatif générique
lié à `AI_ERROR`.

Le fournisseur retourne un tableau PHP. **Il ne crée aucune recommandation et
ne met jamais à jour le stock.** Cette responsabilité appartient au Job.

---

## 11. Étape 9 : validation de la réponse IA dans le Job

Recevoir un JSON bien formé ne suffit pas. Un JSON peut être syntaxiquement
correct mais dangereux sur le plan métier. C’est pourquoi
`validateAdvisorResult()` se trouve directement dans
[`GeneratePlantAdviceJob`](../app/Jobs/GeneratePlantAdviceJob.php).

Le Job vérifie notamment :

1. `space_summary` est une chaîne ;
2. `general_advice` est une chaîne ;
3. `recommendations` est un tableau ;
4. chaque identifiant est un entier ;
5. chaque identifiant appartient à la liste des candidates ;
6. chaque plante n’apparaît qu’une fois ;
7. le rang est compris entre 1 et la limite configurée ;
8. la justification n’est pas vide ;
9. les textes sont nettoyés et limités en longueur ;
10. au moins une recommandation valide reste disponible.

Une entrée invalide est écartée. Si aucune entrée valide ne reste, le Job
lève une exception : le traitement devient un `AI_ERROR` et aucune donnée
incertaine n’est persistée.

### Pourquoi revérifier après l’appel réseau ?

Le catalogue peut changer pendant que Groq répond. Par exemple :

- le gérant désactive la plante ;
- le stock passe à zéro ;
- l’exposition de la plante est corrigée.

Le Job ouvre alors une transaction courte, recharge les plantes avec
`lockForUpdate()` et réapplique `PlantEligibilityService::isEligible()`.
Une plante qui n’est plus éligible est ignorée.

Cette double vérification est volontaire :

```text
Préfiltrage avant l’IA = réduire et cadrer la question
Revérification après l’IA = protéger la persistance finale
```

---

## 12. Étape 10 : transaction et persistance

L’appel à Groq est effectué **avant** la transaction SQL. Une transaction ne
doit pas rester ouverte pendant une attente réseau.

Une fois la réponse contrôlée, le Job exécute une transaction qui :

1. verrouille la demande ;
2. vérifie qu’elle est encore `PROCESSING` ;
3. recharge les plantes recommandées ;
4. vérifie leur éligibilité et leur stock ;
5. crée les lignes `plant_recommendations` valides ;
6. copie la quantité observée dans `stock_quantity_snapshot` ;
7. passe la demande à `COMPLETED` ;
8. enregistre le résumé, le conseil général et `processed_at`.

Le stock courant n’est jamais décrémenté par ce Job. La quantité copiée est
une photographie de l’observation au moment du conseil, pas une réservation de
vente.

Les contraintes uniques de la base empêchent aussi de créer deux fois la même
plante pour la même demande ou le même rang.

---

## 13. Réussite, erreur et nouvelles tentatives

### Demande sans candidate

```text
PlantEligibilityService → collection vide
                       → pas d'appel IA
                       → FAILED
                       → message NO_ELIGIBLE_PLANTS
```

### Erreur IA ou sortie invalide

```text
Appel Groq / validation → exception
                       → tentative suivante si disponible
                       → après la dernière tentative : failed()
                       → FAILED + message AI_ERROR
```

Le Job déclare :

```php
public int $tries = 3;
public int $timeout = 90;

public function backoff(): array
{
    return [10, 30];
}
```

Cela signifie :

- au maximum trois exécutions ;
- une exécution ne doit pas dépasser 90 secondes ;
- après le premier échec, attendre 10 secondes ;
- après le deuxième, attendre 30 secondes ;
- après le dernier échec, Laravel considère le Job comme définitivement
  échoué.

`failed()` ne montre pas l’exception brute au visiteur. Il met la demande à
`FAILED` et conserve le message applicatif générique.

Le tableau `failed_jobs` peut contenir des détails techniques nécessaires au
diagnostic réservé au gérant ou à l’administrateur système. Cela reste
différent de `failure_message` dans `advice_requests`, qui est destiné à l’état
fonctionnel de la demande.

---

## 14. Étape 11 : la page de suivi et le polling

Le visiteur n’a pas besoin de compte pour consulter sa demande : il possède
l’URL avec son token privé.

La route HTML est limitée et cherche la demande par `public_token` :

```php
Route::get('/conseil/suivi/{token}', ...)
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:20,1');
```

La page possède aussi un point de statut JSON minimal :

```php
Route::get('/conseil/suivi/{token}/status', ...)
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:60,1');
```

Ce point n’est pas une API REST séparée. Il sert seulement à la page Blade.

Dans [`track.blade.php`](../resources/views/advice/track.blade.php), Alpine.js :

1. lit le statut initial rendu par Blade ;
2. lance `fetch(statusUrl)` toutes les 5 secondes ;
3. met à jour le libellé `PENDING` ou `PROCESSING` ;
4. arrête le polling quand `terminal` vaut `true` ;
5. recharge la page lorsque la demande devient `COMPLETED` ou `FAILED`.

Le navigateur n’interroge donc pas MySQL directement. Il appelle Laravel, et
Laravel renvoie seulement l’état nécessaire.

---

## 15. Démarrer et observer le traitement

### Démarrer les services

```bash
docker compose up -d
docker compose ps
```

Il faut voir au minimum `app`, `web`, `mysql` et `queue-worker` en cours
d’exécution.

### Préparer la base

Sur une base existante, utilisez :

```bash
docker compose exec app php artisan migrate
```

Pour initialiser les données de démonstration :

```bash
docker compose exec app php artisan db:seed
```

`migrate:fresh --seed` est différent : il supprime les tables avant de les
recréer. Cette commande est réservée au développement lorsque la perte des
données est volontaire.

### Observer le worker

```bash
docker compose logs -f queue-worker
```

Pour vérifier la configuration des migrations :

```bash
docker compose exec app php artisan migrate:status
```

Pour voir les jobs échoués connus par Laravel :

```bash
docker compose exec app php artisan queue:failed
```

Pour exécuter un seul Job en mode diagnostic :

```bash
docker compose exec app php artisan queue:work --once
```

### Vérifier les données avec MySQL

Depuis un shell MySQL autorisé, les tables à observer sont :

```sql
SELECT id, status, processing_started_at, processed_at, failure_message
FROM advice_requests
ORDER BY id DESC;

SELECT id, queue, attempts, reserved_at, available_at
FROM jobs
ORDER BY id DESC;
```

La ligne `jobs` disparaît après une réussite. La ligne de demande, elle, reste
dans `advice_requests` avec son état et son résultat.

---

## 16. Comprendre les tests

Les tests évitent d’appeler un vrai fournisseur pendant la suite automatisée.

### Tester le dispatch

Un test de contrôleur peut utiliser `Queue::fake()` : Laravel prétend placer le
Job en queue et le test vérifie qu’il a bien été dispatché, sans démarrer un
worker.

### Tester le Job

Les tests du Job appellent directement `handle()` avec un faux `PlantAdvisor`
Mockery. Cela permet de vérifier :

- la transition `PENDING` vers `PROCESSING` ;
- l’absence d’appel IA quand aucune plante n’est éligible ;
- le rejet d’un identifiant inventé ;
- le rejet des doublons ;
- la limite du nombre de recommandations ;
- la revérification de l’éligibilité ;
- la reprise d’une demande `PROCESSING` ;
- l’idempotence d’une demande déjà terminée.

Voir [`GeneratePlantAdviceJobTest.php`](../tests/Feature/Jobs/GeneratePlantAdviceJobTest.php).

### Tester l’appel Groq sans réseau

[`GroqPlantAdvisorTest.php`](../tests/Unit/Advice/GroqPlantAdvisorTest.php)
utilise `Http::fake()`. Laravel intercepte alors la requête HTTP et renvoie
une réponse préparée par le test.

Le test peut ainsi vérifier :

- l’URL et le modèle ;
- la présence du schéma JSON ;
- la langue française demandée ;
- la présence de l’identifiant et du nom de la candidate ;
- l’absence de `customer_email`, `price`, `stock_quantity` et `pet_safe` ;
- le comportement en cas de JSON invalide ou de réponse HTTP refusée.

Pour lancer la suite :

```bash
docker compose exec app php artisan test
vendor/bin/pint --test
```

Un test qui appelle Internet n’est pas acceptable dans la suite normale : il
serait lent, instable et pourrait exposer une clé.

---

## 17. Dépannage courant

### Le formulaire reste en `PENDING`

Vérifiez que le worker est actif :

```bash
docker compose ps queue-worker
docker compose logs --tail=100 queue-worker
```

Vérifiez aussi que la variable est bien :

```dotenv
QUEUE_CONNECTION=database
```

et que les tables `jobs` et `failed_jobs` existent.

### Le worker redémarre sans finir le Job

Consultez les logs. Un appel Groq trop lent peut dépasser `--timeout=90` ou le
timeout configuré dans `GROQ_TIMEOUT`. Il faut alors diagnostiquer la durée,
la connexion et les tentatives avant de modifier une limite.

### La demande passe à `FAILED`

Cherchez d’abord le cas métier :

- `Aucune plante ne correspond aux critères indiqués.` signifie qu’aucune
  candidate n’a passé le préfiltrage ;
- `Le service de conseil est temporairement indisponible.` signifie qu’un
  appel IA, un JSON ou une validation n’a pas abouti.

Puis consultez :

```bash
docker compose exec app php artisan queue:failed
docker compose logs --tail=200 queue-worker
```

### Groq ne répond pas

Vérifiez que `AI_PROVIDER=groq`, que `GROQ_API_KEY` existe seulement dans votre
`.env` local, et que l’URL et le modèle sont valides. Ne copiez jamais la clé
dans une issue, un commit ou un document partagé.

### Pourquoi aucune plante n’est enregistrée ?

Laravel peut avoir rejeté les candidates après la réponse IA parce qu’elles
étaient devenues inactives, hors stock ou incompatibles. C’est le comportement
de sécurité attendu : une recommandation invalide ne doit pas être affichée.

---

## 18. Résumé en une phrase par composant

- **Queue** : stockage durable d’un travail à faire plus tard.
- **Worker** : processus qui lit la queue et exécute les Jobs.
- **Job** : classe qui décrit et orchestre une tâche asynchrone.
- **`GeneratePlantAdviceJob`** : traitement complet d’une demande de conseil.
- **`PlantEligibilityService`** : filtre déterministe du catalogue Laravel.
- **`PlantAdvisor`** : contrat commun d’un conseiller.
- **`FakePlantAdvisor`** : conseiller local prévisible, sans réseau.
- **`GroqPlantAdvisor`** : adaptateur HTTP vers Groq.
- **Validation IA** : contrôles qui empêchent une sortie incohérente de devenir
  une donnée métier.
- **Transaction** : bloc SQL court qui persiste un résultat cohérent.
- **Polling** : requêtes périodiques du navigateur pour connaître l’état.
- **`PENDING`** : demande enregistrée, Job en attente.
- **`PROCESSING`** : Job en cours d’exécution.
- **`COMPLETED`** : recommandations contrôlées et persistées.
- **`FAILED`** : traitement terminé sans résultat publiable.

Le parcours complet respecte toujours la même règle :

```text
Laravel filtre → l’IA classe et explique → Laravel vérifie → MySQL persiste
```
