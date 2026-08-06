# Vue d’ensemble de Pépinière IA

## Finalité

Pépinière IA est une application web de gestion de pépinière avec un parcours de conseil botanique. Le gérant tient le catalogue et le stock à jour. Un visiteur décrit son espace sans créer de compte. Laravel sélectionne les plantes compatibles, puis un conseiller logiciel les classe et explique son choix.

Le projet ne confie jamais la décision finale au modèle d’IA. MySQL contient le catalogue réel. Laravel filtre les candidates, contrôle la réponse du conseiller, vérifie une seconde fois le stock et enregistre seulement les recommandations valides.

## Utilisateurs

### Visiteur

Le visiteur peut :

- consulter la page d’accueil ;
- remplir le formulaire `/conseil` sans compte ;
- donner un nom et un email facultatifs ;
- recevoir une URL de suivi contenant un token aléatoire ;
- consulter le statut puis les recommandations persistées.

Le nom et l’email ne partent pas chez Groq. L’email sert seulement de coordonnée facultative dans l’historique actuel. L’envoi automatique du lien par email reste hors MVP.

### Gérant

Le gérant utilise un compte `User` authentifié par session. Il peut :

- se connecter sur `/login` ;
- créer, modifier, rechercher et filtrer des plantes ;
- mettre à jour le prix et le stock ;
- désactiver ou archiver une plante ;
- consulter l’historique et le détail des demandes ;
- gérer son profil et son mot de passe.

L’application ne propose aucune inscription publique. Le premier compte se crée avec `php artisan manager:create` ou avec le seeder configuré par les variables `MANAGER_*`.

## Parcours fonctionnel principal

1. Le navigateur envoie le formulaire public à Laravel.
2. `StoreAdviceRequest` vérifie et normalise les champs.
3. Laravel crée une ligne `advice_requests` au statut `PENDING` avec un token public de 64 caractères hexadécimaux.
4. Laravel place `GeneratePlantAdviceJob` dans la table `jobs`.
5. Le worker réserve la demande et la passe à `PROCESSING`.
6. `PlantEligibilityService` sélectionne les plantes actives, en stock et compatibles.
7. Le `PlantAdvisor` actif classe les candidates et rédige les explications.
8. Le Job contrôle la forme de la réponse, les identifiants, les doublons, les rangs et la limite de résultats.
9. Dans une transaction courte, le Job verrouille les lignes, revalide chaque plante et crée les recommandations.
10. La demande passe à `COMPLETED`. La page de suivi se recharge lorsque son polling détecte cet état.

Sans candidate, Laravel passe directement la demande à `FAILED` avec un message public. Une erreur du fournisseur produit aussi `FAILED` après les tentatives prévues.

## Ce que fait l’IA

L’IA accomplit deux tâches : ordonner une liste déjà autorisée et expliquer les choix en français. Elle reçoit le contexte de l’espace et quelques propriétés des candidates.

Elle ne peut pas :

- interroger ou écrire directement dans MySQL ;
- modifier le stock ou le prix ;
- recommander un identifiant absent de la liste fournie ;
- rendre éligible une plante inactive, épuisée ou incompatible ;
- inventer une plante qui serait ensuite ajoutée au catalogue.

`FakePlantAdvisor` produit une réponse déterministe pour le développement et les tests. `GroqPlantAdvisor` appelle le endpoint Groq seulement lorsque `AI_PROVIDER=groq` et qu’une clé locale existe.

## Pourquoi un monolithe Laravel

Le même processus applicatif gère les pages Blade, l’authentification, la validation, le catalogue, la queue et la persistance. Ce choix réduit le nombre de systèmes à déployer et convient au périmètre du MVP. Les routes JSON de santé et de statut sont des points techniques du monolithe, pas une API REST produit.

Une API séparée, Sanctum ou une SPA ajouteraient une seconde surface d’authentification et un contrat HTTP à maintenir sans répondre à un besoin actuel. Breeze Blade et les sessions couvrent le compte gérant. Blade rend les pages côté serveur ; Alpine.js prend en charge le menu, les modales et le polling léger.

## Choix de la stack

| Élément | Rôle | Motif du choix |
|---|---|---|
| Laravel 13 / PHP 8.3 | Application web et métier | Cadre unique pour HTTP, validation, ORM, queue et tests. |
| Blade | Rendu HTML | Pages serveur simples, sans application frontend séparée. |
| Tailwind CSS | Présentation | Styles composables et compilation intégrée à Vite. |
| Alpine.js | Interactivité locale | Suffisant pour le polling et les composants Breeze. |
| MySQL 8.4 | Données et queue | Une seule base pour le métier, `jobs` et `failed_jobs`. |
| Queue `database` | Traitement IA | L’appel au conseiller ne bloque pas la soumission HTTP. |
| Cache et sessions `file` | État local | Évite Redis pour le volume et le déploiement actuels. |
| Breeze Blade | Authentification | Sessions, connexion, mot de passe et vérification d’email. |
| Pest | Tests | Syntaxe courte au-dessus des outils de test Laravel. |
| Docker Compose | Environnement local | Versions reproductibles de PHP, Nginx et MySQL. |
| GitHub Actions | Intégration continue | Vérifie migrations, tests, assets, dépendances et formatage. |

## Fonctionnalités présentes

- catalogue de plantes avec stock, prix en MAD, recherche et filtres ;
- archivage par suppression logique ;
- authentification et profil du gérant ;
- formulaire public avec validation, consentement et limitation de débit ;
- token public non dérivé de l’identifiant SQL ;
- page de suivi avec polling ;
- préfiltrage déterministe ;
- queue, retries et fake IA ;
- fournisseur Groq optionnel avec réponse JSON structurée ;
- validation défensive et persistance transactionnelle ;
- historique simple pour le gérant ;
- en-têtes de sécurité et tests automatisés.

## Éléments volontairement reportés

Le MVP ne comprend pas la vente en ligne, le panier, le paiement, la réservation du stock, l’envoi automatique du lien par email, la relance manuelle d’une demande, les statistiques, les filtres de période, `pet_safe`, le snapshot du prix ou la comparaison entre stock courant et stock observé.

Le stock et le prix restent utiles sans vente en ligne : le stock empêche de conseiller un produit indisponible et le prix présente le catalogue commercial du gérant. Une phase de vente devra définir les commandes, lignes de commande, paiements et règles de décrément du stock avant d’ajouter ces fonctions.

## Règles métier importantes

- Une plante candidate doit être active et avoir `stock_quantity > 0`.
- Son exposition doit correspondre à la demande.
- Son environnement doit correspondre, sauf `BOTH` qui accepte intérieur et extérieur.
- Ses dimensions doivent respecter les limites configurées pour la taille de l’espace.
- Son niveau d’entretien ne doit pas dépasser la disponibilité du visiteur.
- Une recommandation doit référencer une candidate fournie au conseiller.
- Une plante ne peut apparaître qu’une fois dans une demande.
- Le nombre de recommandations respecte `ADVICE_MAX_RECOMMENDATIONS`.
- Le Job copie la quantité disponible au moment de la persistance, sans la modifier.

Les seuils actuels se trouvent dans `config/advice.php` : petit `60 × 45 cm`, moyen `120 × 80 cm`, grand `240 × 160 cm`.

## État et dette technique connue

Le code correspond au MVP simplifié décrit dans `technical-decisions.md`. Quelques points méritent une décision avant une mise en production :

- valider les seuils de dimensions avec le gérant ;
- décider si l’email facultatif doit déclencher un vrai envoi ;
- choisir une stratégie de remise en file des demandes restées en `PROCESSING` après un arrêt brutal ;
- décider si un résultat devenu vide lors de la seconde vérification doit finir en `FAILED` plutôt qu’en `COMPLETED` ;
- ajouter une contrainte SQL unique sur `(advice_request_id, rank)` si le rang doit être unique au niveau de la base ;
- retirer ou adapter la suppression de compte issue de Breeze si un gérant unique ne doit jamais pouvoir supprimer son propre accès.

Ces points ne remettent pas en cause le fonctionnement démontré, mais ils doivent apparaître dans le backlog avant l’exploitation réelle.
