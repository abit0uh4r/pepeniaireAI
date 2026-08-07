# Guide technique du projet

Cette documentation explique le projet à partir du code présent dans le dépôt. Elle sert de point d’entrée à une personne qui reprend l’application après sa génération initiale.

## Ordre de lecture conseillé

1. [Vue d’ensemble du projet](project-overview.md) : objectifs, utilisateurs, fonctionnalités et choix techniques.
2. [Architecture et flux d’exécution](architecture.md) : couches Laravel, données, requêtes HTTP, queue et IA.
3. [Lecture guidée du code](code-walkthrough.md) : parcours du code avec extraits expliqués en langage simple.
4. [Modèle de données](data-model.md) : tables, colonnes, relations, contraintes et historique des migrations.
5. [Référence des fichiers](file-reference.md) : rôle des dossiers et des fichiers suivis par Git.
6. [Guide de développement](development-guide.md) : installation, commandes, tests, configuration et dépannage.
7. [Glossaire Laravel](glossary.md) : définition des termes rencontrés dans le projet.
8. [Contexte à fournir à ChatGPT](chatgpt-project-context.md) : état autonome et vérifié du projet pour préparer de futures mises à jour.

## Documents de cadrage

- [Décisions techniques](technical-decisions.md) : arbitrages pris pendant la conception.
- [Plan d’implémentation](implementation-plan.md) : phases prévues et critères de validation.
- `Cahier_des_Charges_Pepiniere_IA.docx` : besoin fonctionnel initial.
- `architecture-pepiniere-ia.png` : diagramme fourni au démarrage.

## Limite de cette documentation

Le guide décrit l’état du dépôt au moment de sa rédaction. Le code reste la référence lorsqu’un futur changement n’a pas encore été reporté dans les documents. Une modification d’architecture, de configuration ou de flux doit mettre à jour le document concerné dans le même commit.
