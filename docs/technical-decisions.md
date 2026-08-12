# Décisions techniques

Date de référence : 27 juillet 2026
Statut : décisions proposées pour validation avant installation

## Sources analysées

- `docs/Cahier_des_Charges_Pepiniere_IA.docx`, version 1.1 du 27 juillet 2026 ;
- `docs/architecture-pepiniere-ia.png` ;
- contraintes données pour la préparation du projet.

## Décisions retenues

### TD-001 : Monolithe web Laravel

L’application utilisera Laravel 13 avec PHP 8.3. Laravel rendra les pages avec Blade. Les routes publiques et d’administration resteront dans le groupe `web`.

Le libellé « Laravel API » du diagramme décrit le backend Laravel, pas une API REST séparée. Le MVP ne comportera ni SPA ni couche API autonome.

### TD-002 : Authentification du gérant

Laravel Breeze utilisera son stack Blade et l’authentification par session. `SESSION_DRIVER=file` restera la valeur locale et celle du MVP, sauf contrainte de déploiement validée plus tard.

Les routes et vues d’inscription seront supprimées. Une commande Artisan interactive provisionnera le premier gérant. Les tests utiliseront une factory. Aucune donnée d’accès fixe ne sera versionnée.

Sanctum ne sera pas installé. Le bloc « Auth (Laravel Sanctum) » du diagramme contredit l’authentification web par session demandée.

### TD-003 : Frontend

Blade, Tailwind CSS et les composants Blade porteront l’interface. Alpine.js servira au polling du statut et aux interactions locales.

`GET /conseils/{token}/statut` retournera un fragment d’état JSON minimal sous middleware `web`, avec limitation de débit. Ce point technique ne devient pas une API produit séparée.

### TD-004 : Stockage et services locaux

MySQL 8.4 stockera les données métier, les `jobs` et les `failed_jobs`.

Les réglages obligatoires seront :

```dotenv
QUEUE_CONNECTION=database
CACHE_STORE=file
SESSION_DRIVER=file
ADVICE_MAX_RECOMMENDATIONS=3
AI_DEFAULT_PROVIDER=groq
```

Le projet ne créera pas de tables `sessions` ou `cache`. Il ne créera `job_batches` qu’en cas d’usage futur des batches. Redis et Horizon restent exclus.

Les emails locaux utiliseront le driver `log`. Aucun serveur SMTP de développement ne sera démarré par Docker Compose ; l’envoi du lien reste optionnel et hors du chemin critique du MVP.

### TD-005 : Contrat du conseiller

Le code applicatif définit `PlantAdvisor` directement dans `app/Services`, à côté de `GroqPlantAdvisor`. Aucun dossier `Contracts`, `DTOs` ou `Actions` n’est utilisé pour le MVP ; les échanges avec le conseiller utilisent des tableaux documentés par PHPDoc.

Le cahier des charges impose l’utilisation du SDK officiel `laravel/ai`. Le domaine reste découplé du SDK grâce au contrat `PlantAdvisor`, que `GroqPlantAdvisor` implémente en adaptant l’Agent Laravel AI. Les tests remplacent l’Agent du SDK par `PlantAdviceAgent::fake()` et ne contactent jamais Groq. Aucun dossier `Contracts`, `DTOs` ou `Actions` supplémentaire n’est créé pour cette intégration.

### TD-006 : Limite de confiance IA

Le conseiller recevra :

- les contraintes normalisées de la demande ;
- le texte libre nettoyé ;
- les identifiants et propriétés utiles des seules plantes candidates.

Il ne recevra ni accès à la base, ni capacité d’écriture, ni nom, téléphone ou email du visiteur.

Laravel validera le format, limitera les tailles de texte, rejettera les identifiants absents, rechargera les plantes, vérifiera leur activité, leur éligibilité et leur stock, dédupliquera les résultats puis persistera le tout dans une transaction courte.

### TD-007 : Modèle de statuts

Les transitions admises seront :

```text
PENDING → PROCESSING → COMPLETED
                     ↘ FAILED
FAILED → PENDING, uniquement lors d’une relance autorisée ultérieure
```

Une absence de candidate donne `FAILED` sans appel au conseiller. Une réponse IA invalide donne également `FAILED`. Les deux causes applicatives sont `NO_ELIGIBLE_PLANTS` et `AI_ERROR`, mais aucun champ `failure_code` n’est persisté : seule une formulation nettoyée est conservée dans `failure_message`.

### TD-008 : Idempotence et concurrence

Le traitement combinera :

- une vérification du statut ;
- un verrou de ligne au moment des transitions et de la finalisation ;
- une protection contre le chevauchement ou l’unicité du Job ;
- les contraintes uniques `(advice_request_id, plant_id)` et `(advice_request_id, rank)` ;
- une transaction courte pour remplacer ou créer un résultat complet.

Le Job n’ouvrira aucune transaction pendant l’appel au conseiller. Un Job qui trouve une demande `COMPLETED` s’arrêtera.

### TD-009 : Token public

Chaque demande recevra 32 octets aléatoires encodés en hexadécimal, stockés dans un `CHAR(64)` unique. Les routes publiques chercheront la demande par ce token et répondront par 404 pour toute valeur inconnue.

Le token ne sera ni dérivé de l’identifiant, ni affiché dans les logs applicatifs. Les identifiants numériques resteront réservés à l’administration authentifiée.

### TD-010 : Données catalogue

Les statuts, niveaux, environnements et tailles utilisent des backed enums PHP. L’exposition d’une plante devient une valeur unique stockée dans un `ENUM` simple ; le préfiltrage utilise une comparaison directe et non `whereJsonContains`.

`pet_safe` et la règle BR-06 sont reportés après le MVP.

Les plantes utilisent la suppression logique. La clé étrangère des recommandations utilise une suppression restreinte. Seule la quantité en stock est copiée dans la recommandation lors de la finalisation ; le snapshot de prix est reporté.

### TD-011 : Seuils d’espace

Les seuils SMALL, MEDIUM et LARGE pour la hauteur et la largeur adulte seront centralisés dans `config/advice.php`. Aucun seuil n’est défini dans les documents sources.

Les valeurs initiales devront recevoir une validation métier avant la phase de préfiltrage. Les tests figeront ensuite le comportement choisi.

### TD-012 : Docker Compose

L’environnement cible comprendra :

- `app`, pour PHP-FPM 8.3 et l’application ;
- `web`, pour Nginx ;
- `mysql`, pour MySQL 8.4 ;
- `worker`, construit depuis la même image que `app` ;
- `node`, sous forme de profil ou de commande ponctuelle pour les assets.

Le worker lancera `php artisan queue:work` avec des limites explicites. `app` et `worker` partageront le code et les volumes de stockage nécessaires aux drivers fichiers dans l’environnement local.

### TD-013 : Qualité et CI

Pest couvrira les tests unitaires, fonctionnels et d’intégration. Laravel Pint contrôlera le style. La CI utilisera MySQL 8.4 comme service, construira les assets et n’appellera aucun fournisseur IA.

La commande de référence sera `php artisan test`. Les filtres Pest pourront accélérer le travail local, mais la CI exécutera toute la suite.

### TD-014 : Contrat IA et exécution asynchrone

Le contrat applicatif `PlantAdvisor` reçoit un tableau de contexte ne contenant ni nom, téléphone ni adresse email, ainsi qu’une collection de plantes candidates fournie par Laravel. Le conteneur Laravel lie ce contrat à `GroqPlantAdvisor`, qui utilise l’Agent structuré du SDK `laravel/ai`.

`GeneratePlantAdviceJob` reçoit l’identifiant de la demande, reconstruit le contexte et demande à `PlantEligibilityService` les candidates au moment de l’exécution. Il utilise la connexion database, possède trois tentatives, un timeout de 90 secondes et des délais de reprise bornés. La validation défensive de la réponse IA réside directement dans le Job avant la persistance transactionnelle.

### TD-015 : Valeurs initiales du préfiltrage

Les documents sources ne donnent pas de seuils numériques pour la taille de l’espace. Pour rendre la phase 7 déterministe, la configuration utilise provisoirement les limites suivantes : SMALL (60 × 45 cm), MEDIUM (120 × 80 cm) et LARGE (240 × 160 cm), dans l’ordre hauteur × largeur adulte.

Le filtre exige une plante active, en stock, compatible avec l’environnement et l’exposition unique demandée, dont le niveau d’entretien ne dépasse pas la disponibilité déclarée. Une plante `BOTH` est compatible avec les deux environnements. Ces seuils sont révisables avant une mise en production.

La migration du catalogue existant conserve la première valeur de l’ancien tableau JSON d’expositions. Une valeur absente ou non reconnue devient `PARTIAL_SHADE`, afin que la colonne `ENUM` reste non nulle et exploitable.

### TD-016 : Validation dans le Job et snapshot de quantité

La validation défensive intégrée au Job écarte chaque entrée invalide (identifiant absent des candidates, plante inactive ou en rupture, doublon, rang hors limite ou justification vide) et conserve les entrées valides jusqu’à `ADVICE_MAX_RECOMMENDATIONS`. Une sortie IA sans recommandation valide relève de `AI_ERROR`.

La persistance est exécutée dans une transaction SQL courte après l’appel au conseiller. Les plantes recommandées sont rechargées avec verrouillage, puis leur activité et leur stock sont vérifiés une seconde fois. Chaque recommandation copie uniquement la quantité observée ; aucune écriture ne modifie le stock courant. La contrainte unique `(advice_request_id, plant_id)` protège les replays.

### TD-017 : Fournisseur Groq unique

`GroqPlantAdvisor` utilise le SDK officiel `laravel/ai` avec le fournisseur `Lab::Groq`. L’Agent `App\Ai\Agents\PlantAdviceAgent` porte les instructions francophones, le schéma de sortie structurée, le timeout et la limite de tokens. La clé, l’URL et le modèle sont configurés dans `config/ai.php` et les variables d’environnement correspondantes.

Le SDK demande une sortie structurée JSON lorsque le modèle le supporte. La réponse est ensuite transmise au Job pour validation métier indépendante. Le fournisseur ne reçoit ni nom, téléphone ni email, et seulement les plantes candidates avec leurs propriétés botaniques utiles. Groq est le fournisseur unique et la clé reste obligatoire uniquement dans l’environnement qui exécute réellement le Job.

### TD-018 : En-têtes et cache des pages publiques

Le middleware global `SecurityHeaders` ajoute les en-têtes de défense communs (`nosniff`, anti-framing, politique de référent, permissions minimales et isolation d’ouverture). Les pages et statuts identifiés par token public sont marqués `private, no-store` afin d’éviter une conservation intermédiaire de données de suivi. HSTS n’est ajouté que lorsque la requête est HTTPS.

### TD-019 : Consultation publique et audit gérant

La page publique recharge son rendu Blade lorsque le polling détecte un état terminal. Elle affiche uniquement les recommandations persistées, avec les textes échappés et le snapshot de quantité. La comparaison avec le stock courant, le snapshot de prix, les filtres par période et les statistiques du tableau de bord sont reportés après le MVP. L’administration expose un historique simple et un détail derrière `auth` et `verified` ; l’identifiant numérique reste réservé à cet espace privé.

L’interface adopte un système visuel commun « carnet botanique » réalisé avec Blade, Tailwind CSS et Alpine.js. Aucun composant SPA ni bibliothèque JavaScript supplémentaire n’est introduit.

## Incohérences et ambiguïtés relevées

| Sujet | Constat | Arbitrage |
|---|---|---|
| Backend | Le diagramme affiche « Laravel API » alors que le mandat interdit une API REST séparée. | Backend monolithique Laravel, routes `web`, Blade. |
| Authentification | Le diagramme place Sanctum dans le backend ; le mandat impose une session web avec Breeze Blade. | Breeze Blade et sessions, sans Sanctum. |
| Inscription | Le cahier des charges dit « désactivable en production » ; le mandat exige qu’elle soit désactivée. | Aucune route publique d’inscription dans tous les environnements. |
| Fournisseur réel | Le cahier des charges impose une fonctionnalité IA réelle via `laravel/ai`. | Groq est le fournisseur unique ; préfiltrage et validation défensive Laravel restent obligatoires avant toute persistance. |
| SDK IA | Le cahier impose une fonctionnalité IA via `laravel/ai`, alors que l’ancienne implémentation utilisait le client HTTP Laravel. | Installer `laravel/ai`, utiliser un Agent structuré et le cacher derrière `PlantAdvisor`. |
| Absence de candidate | Le flux parle d’un résultat sans appel IA et la liste des causes présente `NO_ELIGIBLE_PLANTS`. | Résultat `FAILED` avec message nettoyé, sans champ de code. |
| Tables techniques | Le cahier cite `sessions`, `cache` et `job_batches`, incompatibles ou inutiles avec les réglages imposés. | Créer seulement `jobs` et `failed_jobs`. |
| Nom du Job | Le diagramme utilise `AnalyzeAdviceRequestJob`, le texte `GeneratePlantAdviceJob`. | Retenir `GeneratePlantAdviceJob`. |
| Stockage local | Le diagramme prévoit pièces jointes et documents, absents du périmètre MVP. | Aucun stockage documentaire métier dans le MVP. |
| Email | Le flux montre un email optionnel, sans besoin de serveur SMTP local. | Utiliser le driver `log` ; garder l’envoi du lien hors du chemin critique et hors MVP initial. |
| Description libre | Le champ est décrit comme « obligatoire ou fortement recommandé ». | Le rendre obligatoire avec limites de longueur. |
| Expositions | Le modèle hésite entre JSON et SET. | Une valeur `ENUM` simple castée vers `Exposure`. |
| Seuils de taille | BR-04 exige des seuils qui ne sont pas fournis. | Configuration dédiée, valeurs à faire valider avant implémentation du filtre. |
| Relance | Le diagramme parle de retries automatiques et le cahier d’une relance manuelle optionnelle. | Retries bornés dès le MVP ; relance manuelle dans une phase optionnelle. |
| Suppression | Le CRUD suggère une suppression, mais l’historique doit rester intact. | Désactivation et soft delete ; aucune suppression physique d’une plante référencée. |

## Points reportés

Ces sujets ne bloquent ni l’installation ni le catalogue :

- valeurs des seuils de hauteur et largeur pour SMALL, MEDIUM et LARGE ;
- collecte facultative du prénom et du téléphone du visiteur ; aucune adresse email visiteur ;
- ajout de la relance manuelle des demandes échouées ;
- conservation éventuelle d’une réponse IA brute nettoyée ;
- sécurité animale (`pet_safe`) et règle BR-06 ;
- snapshot du prix et comparaison avec le stock courant ;
- filtres d’historique par période et statistiques du tableau de bord ;
- test manuel Groq opt-in avec une clé locale ;
- envoi du lien public par email.

Ils devront être décidés avant la phase qui les utilise.

### TD-020 : Devise d’affichage et transport email local

Les montants sont stockés comme des décimaux SQL sans changement de schéma et sont affichés en dirhams marocains (`MAD`). Le formateur de prix conserve les valeurs décimales sous forme de chaînes afin de ne pas introduire de flottants dans la présentation.

Mailpit est retiré de l’environnement local à la demande du porteur du projet. Laravel utilise `MAIL_MAILER=log` ; aucun port SMTP ou volume Mailpit ne fait partie de Docker Compose.

### TD-021 : Restauration des plantes archivées

L’archivage d’une plante reste une suppression logique (`deleted_at`) et ne doit pas rendre la fiche irrécupérable pour le gérant. Une page privée `/admin/plants/archived` utilise `onlyTrashed()` pour rechercher les fiches archivées et propose une action `restore` protégée par la policy du catalogue.

La restauration annule le soft delete et remet `is_active` à `true`. La plante réapparaît dans le catalogue, mais elle ne devient candidate au conseil que si son stock et tous ses critères déterministes sont encore compatibles. Aucune suppression physique ni route publique n’est ajoutée.

### TD-022 : Téléphone de contact du visiteur

Le formulaire public peut recueillir un numéro de téléphone facultatif afin que le gérant puisse recontacter le visiteur au sujet de sa demande. Le numéro est validé comme une chaîne de 30 caractères maximum, avec les chiffres et les séparateurs téléphoniques courants (`+`, espaces, parenthèses, points et tirets).

Le numéro est stocké dans `advice_requests.customer_phone` et affiché uniquement dans l’administration authentifiée. Il n’est jamais rendu sur la page publique de suivi, inclus dans le contexte du conseiller IA ou envoyé à Groq. L’absence de numéro ne bloque pas la demande de conseil.
