# Implementation Plan: Réservation inter-agences

**Branch**: `001-reservation-inter-agences` | **Date**: 2026-10-09 | **Spec**: [spec.md](spec.md)

## Summary

Un back Laravel expose une API JSON qui porte toutes les règles R1 à R7 ; un front Nuxt d'une page consomme cette API. Les règles vivent uniquement côté back : le front affiche les motifs renvoyés, il ne les recalcule jamais. Les données des consignes sont chargées par un seeder.

## Affected Repos

- `back` — Laravel 13, PHP 8.4, SQLite, PHPUnit
- `web` — Nuxt 4, TypeScript

Les deux vivent dans le même dépôt `Mr-Vallet` (choix du binôme, écart assumé à la convention multi-dépôts de spec-kit).

## Technical Context

- **Langages** : PHP 8.4 (back), TypeScript (web)
- **Stockage** : SQLite, base reconstruite et seedée au démarrage
- **Tests** : PHPUnit, un test de feature par test de la spec
- **Exécution** : Docker Compose (`back` sur le port 8000, `web` sur le port 3000) ; rien à installer sur le poste
- **Date du jour** : configurable, `VALLET_TODAY=2026-10-12` par défaut

## Data Model

- `agencies` : `id`, `name`
- `machines` : `id`, `ref` (unique), `type`, `agency_id`, `last_vgp_at` (date, nullable), `workshop_until` (date, nullable), `workshop_note` (nullable)
- `reservations` : `id`, `machine_id`, `client`, `starts_at`, `ends_at`, `entered_by_agency_id`

Une machine est une nacelle soumise à la VGP quand son type commence par « Nacelle ».

## API

| Méthode | Route | Rôle |
|---|---|---|
| GET | `/api/agencies` | liste des 7 agences |
| GET | `/api/machine-types` | types de machines |
| GET | `/api/machines?type=&from=&to=` | parc, avec disponibilité et motifs sur la période |
| GET | `/api/reservations` | planning de toutes les agences |
| POST | `/api/reservations` | réserver ; 422 avec les motifs si une règle est violée |
| DELETE | `/api/reservations/{id}` | annuler |
| GET | `/api/anomalies` | réservations existantes qui violent R1 ou R3 |
| PATCH | `/api/machines/{ref}/workshop` | `{ until: date|null, note }` |
| PATCH | `/api/machines/{ref}/vgp` | `{ last_vgp_at: date }` |

## Règles (back)

Une classe `ReservationRules` renvoie la liste des violations pour une machine et une période, en ignorant éventuellement une réservation donnée :

- R1 chevauchement : `existing.starts_at <= to && existing.ends_at >= from`
- R2 atelier : `workshop_until !== null && from <= workshop_until`
- R3 VGP : nacelle et (`last_vgp_at` nul ou `last_vgp_at + 6 mois < to`)
- R4 / R5 / R6 : validation de la requête (`FormRequest`)

La recherche et la réservation passent par la même classe ; les anomalies aussi (R1 entre réservations existantes, R3 sur chaque réservation de nacelle).

## Web

Une page avec quatre onglets : **Rechercher et réserver**, **Planning**, **Anomalies**, **Atelier**. Sélecteur « Je suis : <agence> » en haut. Les messages de refus renvoyés par l'API sont affichés tels quels.

## Hors périmètre

Voir « Ce que l'outil ne fait pas ce matin » dans la spec.
