# Vallet Location Constitution

## Core Principles

### I. La spec d'abord

Toute évolution commence par la spec (`SPEC.md` et `specs/001-reservation-inter-agences/spec.md`), puis le journal des ajouts, puis le code, puis les tests. Un comportement absent de la spec n'existe pas ; un écart découvert dans le code se corrige d'abord dans la spec.

### II. Une règle, un test

Chaque règle métier a au moins un test PHPUnit qui la vérifie et un test de la spec rejouable dans le navigateur. Une fonctionnalité n'est pas terminée tant que `php artisan test` ne passe pas et que le test de la spec n'a pas été joué sur le prototype.

### III. Origine tracée

Chaque règle ajoutée après la première spec indique d'où elle vient : « retour de Brice », « carte révélation N » ou « initiative du binôme, non demandée par Brice ». Les initiatives sont listées dans le journal des ajouts. Aucune règle ne vient de l'IA sans avoir été validée par le binôme.

### IV. Les règles vivent dans le back

Le back Laravel est la seule source des règles et des droits. Le front Nuxt affiche les motifs de refus et les droits renvoyés par l'API ; il ne les recalcule jamais.

### V. Les données des consignes

Le prototype se démontre avec les données exactes du dossier (agences, machines, réservations, date du jour simulée au 12/10/2026). Après chaque série de tests, les données sont réinitialisées pour que Brice retrouve l'état de départ.

## Contraintes

- Stack : Laravel (`back/`) et Nuxt (`web/`) dans le même dépôt `Mr-Vallet`, lancés par Docker Compose. Rien d'autre à installer sur le poste.
- Les identifiants de démonstration sont écrits dans le README, nulle part ailleurs.
- Ce que l'outil ne fait pas est écrit dans la spec plutôt que laissé implicite.

## Workflow

Spec → journal des ajouts → code → tests (PHPUnit et navigateur) → réinitialisation des données → commit et push. Chaque recette de Brice et chaque carte révélation suit ce cycle complet.

## Governance

Cette constitution prime sur les habitudes de développement. La modifier demande l'accord du binôme et une mise à jour de ce fichier.

**Version**: 1.0.0 | **Ratified**: 2026-10-09 | **Last Amended**: 2026-10-09
