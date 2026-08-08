# Lecture guidée du code

Ce guide suit une demande depuis le navigateur jusqu’à la base de données. Il explique ensuite le catalogue, l’authentification, l’interface et les tests. Les extraits viennent du code du projet, avec quelques lignes retirées pour garder l’explication lisible.

## 1. Le point d’entrée : les routes

Laravel lit les routes web dans `routes/web.php`.

```php
Route::get('/conseil', [AdviceRequestController::class, 'create'])
    ->name('advice.create');

Route::post('/conseil', [AdviceRequestController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('advice.store');
```

La première route affiche le formulaire. La seconde reçoit les données envoyées par le visiteur. Le middleware `throttle:10,1` limite une adresse IP à dix soumissions par minute.

Une route nommée évite d’écrire l’URL dans plusieurs fichiers. Blade peut générer le lien avec `route('advice.create')`. Si l’URL change, le nom reste stable.

Les routes de suivi imposent le format du token :

```php
Route::get('/conseil/suivi/{token}', [AdviceRequestController::class, 'track'])
    ->where('token', '[a-f0-9]{64}')
    ->middleware('throttle:20,1')
    ->name('advice.track');
```

Laravel rejette une valeur qui ne contient pas 64 caractères hexadécimaux. Cette vérification intervient avant le contrôleur.

L’administration utilise un groupe protégé :

```php
Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::resource('plants', PlantController::class)->except(['show']);
    });
```

`auth` exige une session connectée. `verified` exige que Laravel ait marqué l’adresse du gérant comme vérifiée. Le préfixe place les pages sous `/admin`.

## 2. Le formulaire Blade

`resources/views/advice/create.blade.php` contient le formulaire public.

```blade
<form method="POST" action="{{ route('advice.store') }}">
    @csrf
    <!-- champs du formulaire -->
</form>
```

`@csrf` ajoute un jeton de sécurité caché. Laravel compare ce jeton avec la session avant d’accepter le formulaire. Un autre site ne peut donc pas soumettre le formulaire au nom du visiteur.

Chaque champ possède un label, une valeur `old()` et un composant d’erreur :

```blade
<label for="environment">Environnement</label>
<select id="environment" name="environment" required>
    <!-- options -->
</select>
<x-input-error :messages="$errors->get('environment')" />
```

`old('environment')` restitue la valeur saisie après une erreur. Le visiteur ne recommence pas tout le formulaire.

Le formulaire collecte un prénom facultatif, les contraintes de l’espace et une description. Il ne demande aucune adresse email.

## 3. La validation HTTP

Laravel injecte `StoreAdviceRequest` dans le contrôleur. Cette classe vérifie les données avant l’exécution de `store()`.

```php
public function rules(): array
{
    return [
        'customer_name' => ['nullable', 'string', 'max:120'],
        'environment' => [
            'required',
            Rule::in(['INDOOR', 'OUTDOOR']),
        ],
        'exposure' => ['required', Rule::enum(Exposure::class)],
        'space_size' => ['required', Rule::enum(SpaceSize::class)],
        'maintenance_availability' => ['required', Rule::enum(Level::class)],
        'free_text_description' => ['required', 'string', 'min:20', 'max:5000'],
        'consent' => ['accepted'],
    ];
}
```

Une règle `enum` accepte seulement une valeur déclarée dans l’enum PHP. Le navigateur peut envoyer n’importe quel texte malgré le `<select>` HTML ; Laravel bloque les valeurs inconnues côté serveur.

La validation HTTP contrôle la forme de la demande. Elle ne choisit aucune plante. `PlantEligibilityService` porte cette seconde responsabilité.

## 4. Le contrôleur public

`AdviceRequestController::store()` reste court :

```php
public function store(StoreAdviceRequest $request): RedirectResponse
{
    $data = $request->validated();
    unset($data['consent']);

    $adviceRequest = AdviceRequest::query()->create($data);
    GeneratePlantAdviceJob::dispatch($adviceRequest->getKey());

    return to_route('advice.track', [
        'token' => $adviceRequest->public_token,
    ]);
}
```

Le contrôleur suit quatre étapes :

1. il récupère les champs validés ;
2. il retire `consent`, car cette case ne correspond pas à une colonne ;
3. il crée la demande puis place un Job dans la queue ;
4. il redirige vers le suivi public.

L’appel IA ne s’exécute pas pendant la requête HTTP. Le visiteur reçoit donc sa page de suivi même si Groq met plusieurs secondes à répondre.

## 5. Le modèle `AdviceRequest`

`app/Models/AdviceRequest.php` représente une ligne de la table `advice_requests`.

### Champs modifiables

```php
#[Fillable([
    'customer_name',
    'environment',
    'exposure',
    'space_size',
    'maintenance_availability',
    'free_text_description',
    'status',
])]
```

`Fillable` autorise l’assignation de masse de ces attributs avec `create()` ou `update()`. Eloquent ignore un champ envoyé en plus si `Fillable` ne le déclare pas.

### Valeurs par défaut

```php
protected static function booted(): void
{
    static::creating(function (self $request): void {
        $request->public_token ??= self::generatePublicToken();
        $request->status ??= AdviceRequestStatus::PENDING;
    });
}
```

Le hook `creating` s’exécute avant l’insertion SQL. Il crée le token et le statut si le code appelant ne les fournit pas. Une factory, un contrôleur et une commande obtiennent ainsi le même comportement.

```php
public static function generatePublicToken(): string
{
    return bin2hex(random_bytes(32));
}
```

`random_bytes(32)` produit 32 octets aléatoires. `bin2hex()` les transforme en 64 caractères adaptés à une URL. Le token ne révèle pas l’identifiant numérique de la demande.

### Casts

```php
'environment' => PlantEnvironment::class,
'status' => AdviceRequestStatus::class,
'processed_at' => 'datetime',
```

Eloquent transforme une chaîne SQL comme `PENDING` en objet `AdviceRequestStatus`. Le code peut appeler `$request->status->isTerminal()` au lieu de comparer des chaînes dans chaque fichier.

## 6. La queue

`GeneratePlantAdviceJob::dispatch($id)` sérialise l’identifiant de la demande dans la table `jobs`. Le conteneur `queue-worker` exécute :

```text
php artisan queue:work --sleep=3 --tries=3 --timeout=90 --max-time=3600
```

Le Job définit les mêmes bornes :

```php
public int $tries = 3;
public int $timeout = 90;

public function backoff(): array
{
    return [10, 30];
}
```

Laravel attend 10 secondes avant le deuxième essai, puis 30 secondes avant le troisième. Un appel bloqué ne dépasse pas 90 secondes.

## 7. Le début du Job

Le Job recharge la demande à partir de son identifiant :

```php
$adviceRequest = AdviceRequest::query()->find($this->adviceRequestId);

if ($adviceRequest === null || $adviceRequest->status->isTerminal()) {
    return;
}
```

Un retry peut relancer la même tâche. Le Job quitte le traitement si la demande a disparu ou possède déjà un résultat terminal. Cette condition protège l’idempotence.

Le claim réserve ensuite la demande :

```php
AdviceRequest::query()
    ->whereKey($adviceRequest->getKey())
    ->where('status', AdviceRequestStatus::PENDING->value)
    ->update([
        'status' => AdviceRequestStatus::PROCESSING->value,
        'processing_started_at' => now(),
    ]);
```

La requête SQL modifie la ligne seulement si son statut vaut encore `PENDING`. Deux workers qui prennent le même Job ne peuvent pas tous les deux réussir ce claim.

## 8. Le préfiltrage Laravel

Le Job appelle `PlantEligibilityService` avant le conseiller :

```php
$candidatePlants = $eligibilityService->eligiblePlants($adviceRequest);

if ($candidatePlants->isEmpty()) {
    $this->failRequest(
        $adviceRequest,
        AdviceFailure::NO_ELIGIBLE_PLANTS,
    );

    return;
}
```

Laravel n’appelle pas l’IA lorsque le catalogue ne contient aucune candidate.

Le service commence par un filtre SQL :

```php
return Plant::query()
    ->active()
    ->inStock()
    ->where('exposure', $adviceRequest->exposure->value)
    ->orderBy('id')
    ->get()
    ->filter(fn (Plant $plant): bool =>
        $this->isEligible($plant, $adviceRequest)
    )
    ->values();
```

Les scopes `active()` et `inStock()` viennent du modèle `Plant`. La méthode `isEligible()` contrôle ensuite l’environnement, les dimensions et l’entretien.

```php
return $this->levelRank($plant->maintenance_level)
    <= $this->levelRank($adviceRequest->maintenance_availability);
```

Le service convertit `LOW`, `MEDIUM` et `HIGH` en nombres. Une plante exigeant peu d’entretien convient donc à une personne qui accepte un niveau moyen, mais l’inverse échoue.

Les seuils de dimensions se trouvent dans `config/advice.php`. Le code refuse une plante si la configuration manque ou si ses dimensions ne sont pas renseignées.

## 9. Le contrat `PlantAdvisor`

Le Job dépend d’une interface :

```php
interface PlantAdvisor
{
    public function advise(
        array $context,
        Collection $candidatePlants,
    ): array;
}
```

`AppServiceProvider` choisit l’implémentation à partir de la configuration :

```php
return match (config('advice.ai_provider')) {
    'groq' => new GroqPlantAdvisor(...),
    default => throw new InvalidArgumentException('Only Groq is supported.'),
};
```

Le Job ne connaît ni HTTP ni Groq. Il demande un conseil à l’objet que Laravel lui injecte.

### `GroqPlantAdvisor`

Groq reçoit le contexte et les candidates sous forme JSON. Le service n’envoie ni prénom, ni prix, ni quantité de stock. Son schéma demande trois champs :

```json
{
  "space_summary": "...",
  "general_advice": "...",
  "recommendations": [
    {"plant_id": 12, "rank": 1, "reason": "..."}
  ]
}
```

Le prompt demande des textes français et interdit les identifiants absents de la liste. Laravel contrôle quand même le résultat après l’appel.

## 10. La validation de la réponse IA

`validateAdvisorResult()` vérifie les champs de premier niveau, puis chaque recommandation. Une entrée disparaît du résultat si :

- son identifiant ne figure pas parmi les candidates ;
- la même plante apparaît deux fois ;
- le rang sort de la limite ;
- la justification manque ;
- un champ possède le mauvais type.

Le Job coupe les textes aux longueurs prévues et trie les recommandations par rang. Il lève une exception si aucune recommandation valide ne reste. Laravel transforme cet échec terminal en message public `AI_ERROR`.

Le projet ne stocke pas la réponse brute de Groq. Cette règle évite de conserver un contenu externe inutile ou des données difficiles à auditer.

## 11. La transaction finale

L’appel Groq se termine avant l’ouverture de la transaction. Le Job verrouille ensuite la demande et les plantes :

```php
DB::transaction(function () use ($eligibilityService, $validatedResult): void {
    $adviceRequest = AdviceRequest::query()
        ->whereKey($this->adviceRequestId)
        ->lockForUpdate()
        ->first();

    $plants = Plant::query()
        ->whereKey($plantIds)
        ->lockForUpdate()
        ->get()
        ->filter(fn (Plant $plant): bool =>
            $eligibilityService->isEligible($plant, $adviceRequest)
        );
});
```

Une plante peut être désactivée ou épuisée pendant l’appel réseau. Le second passage dans `isEligible()` élimine cette plante avant l’insertion.

Chaque recommandation garde la quantité observée :

```php
PlantRecommendation::query()->create([
    'advice_request_id' => $adviceRequest->getKey(),
    'plant_id' => $plant->getKey(),
    'rank' => $recommendation['rank'],
    'reason' => $recommendation['reason'],
    'stock_quantity_snapshot' => $plant->stock_quantity,
]);
```

Le Job copie le stock pour l’audit. Il ne retire aucune unité du stock. Une recommandation ne représente ni une vente ni une réservation.

## 12. La page de suivi

`resources/views/advice/track.blade.php` reçoit la demande depuis le contrôleur. Blade choisit le bloc correspondant au statut.

Alpine.js interroge le statut toutes les cinq secondes :

```javascript
init() {
    if (!this.terminal) {
        this.timer = setInterval(() => this.refresh(), 5000);
    }
}
```

Le point JSON retourne seulement :

```json
{
  "status": "PROCESSING",
  "label": "En traitement",
  "terminal": false,
  "updated_at": "2026-08-07T10:00:00+00:00"
}
```

Alpine recharge la page lorsqu’un statut devient terminal. Blade affiche alors les recommandations persistées. Le navigateur ne reçoit pas une réponse IA provisoire.

Blade affiche les textes avec `{{ $value }}`. Cette syntaxe échappe les balises HTML. Une chaîne contenant `<script>` apparaît comme du texte et ne s’exécute pas.

## 13. Le catalogue et le stock

`PlantController` suit le même découpage que le parcours public :

- la route choisit la méthode ;
- `StorePlantRequest` ou `UpdatePlantRequest` valide le formulaire ;
- la policy vérifie le gérant ;
- le contrôleur utilise Eloquent ;
- Blade affiche le résultat.

Exemple de création :

```php
public function store(StorePlantRequest $request): RedirectResponse
{
    Plant::query()->create($request->validated());

    return to_route('admin.plants.index')
        ->with('status', 'La plante a été ajoutée au catalogue.');
}
```

Le prix utilise une valeur décimale. `MoneyFormatter` l’affiche en MAD sans conversion vers un nombre flottant.

L’archivage combine deux actions : le contrôleur désactive la plante puis appelle `delete()`. Le trait `SoftDeletes` remplit `deleted_at` au lieu de supprimer la ligne. L’historique des recommandations peut donc retrouver une plante archivée.

Le gérant peut ensuite ouvrir `/admin/plants/archived`. `PlantController::archived()` utilise `onlyTrashed()` pour ne charger que les fiches archivées. Le bouton de restauration utilise une route `withTrashed()` afin que Laravel puisse retrouver la ligne malgré `deleted_at`. `restore()` enlève l’archive, mais conserve `is_active = false` : la remise en ligne reste une décision explicite du gérant.

## 14. Breeze et le compte gérant

Breeze fournit les contrôleurs et vues de connexion, vérification d’email, mot de passe oublié et profil. Le projet a retiré les routes d’inscription.

Le navigateur conserve un cookie de session. Laravel stocke les données de session dans `storage/framework/sessions` avec `SESSION_DRIVER=file`.

Le premier gérant vient de la commande :

```bash
php artisan manager:create
```

`ManagerProvisioner` valide l’email, hache le mot de passe par le cast du modèle `User` et marque le compte comme vérifié. Le formulaire public de conseil ne crée aucun `User`.

## 15. Les migrations, seeders et factories

Une migration modifie le schéma. Laravel exécute les fichiers dans l’ordre de leur timestamp. Le projet conserve les anciennes étapes, puis applique des migrations de simplification. Une base vide arrive ainsi au schéma actuel par le même chemin qu’une base existante.

Un seeder crée des données de démarrage. `PlantSeeder` fournit 23 plantes de démonstration. `ManagerSeeder` crée le compte configuré si `MANAGER_PASSWORD` possède une valeur.

Une factory crée des données pour un test. Elle produit une plante ou une demande avec des valeurs valides, puis le test remplace seulement les attributs utiles au scénario.

## 16. Lire un test Pest

Ce test vérifie le dispatch sans exécuter le Job :

```php
Queue::fake();

$response = $this->post(route('advice.store'), advicePayload());

Queue::assertPushed(
    GeneratePlantAdviceJob::class,
    fn (GeneratePlantAdviceJob $job): bool =>
        $job->adviceRequestId === $request->id,
);
```

`Queue::fake()` remplace la queue pendant le test. L’assertion confirme que le contrôleur a préparé le bon Job.

Les tests du Job l’instancient puis appellent `handle()` avec un conseiller simulé. Ils peuvent ainsi renvoyer un identifiant inventé, un doublon ou un JSON invalide et vérifier que Laravel ne le persiste pas.

## 17. Docker et CI

`compose.yaml` lance quatre services : Nginx, PHP-FPM, MySQL et le worker. `app` et `queue-worker` partagent la même image, donc ils exécutent le même code PHP avec les mêmes dépendances.

GitHub Actions reconstruit le projet sur une machine neuve. La CI installe les dépendances, construit les assets, recrée MySQL, lance les seeders, exécute Pest puis Pint. Elle recherche aussi des motifs de clés secrètes dans les fichiers suivis.

## 18. Où modifier le code selon le besoin

| Besoin | Point de départ | Autres fichiers probables |
|---|---|---|
| Ajouter un champ plante | migration | modèle, Request, Blade, factory, seeder, tests |
| Changer une règle d’éligibilité | `PlantEligibilityService` | `config/advice.php`, tests unitaires et Job |
| Modifier la réponse IA | `PlantAdvisor` et Job | Groq, page de suivi, tests HTTP simulés |
| Ajouter une page admin | `routes/web.php` | contrôleur, policy, vue, test Feature |
| Modifier le polling | `advice/track.blade.php` | route `status`, contrôleur, tests publics |
| Modifier le compte gérant | fichiers Auth/Profile | routes auth, modèle `User`, tests Auth |

Commencez par le test qui décrit le comportement attendu. Modifiez ensuite la couche responsable. Une règle botanique appartient au service d’éligibilité ; un contrôle de format HTTP appartient à une Form Request.
