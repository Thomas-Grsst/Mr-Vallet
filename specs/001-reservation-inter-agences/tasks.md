# Tasks: Réservation inter-agences

**Input**: [spec.md](spec.md), [plan.md](plan.md)

## Phase 1 — Setup

- [x] T001 Créer le projet Laravel dans `back/` et le projet Nuxt dans `web/`
- [x] T002 `docker-compose.yml` à la racine : services `back` (8000) et `web` (3000)
- [x] T003 `back/config/vallet.php` : date du jour (`VALLET_TODAY`, défaut 2026-10-12) et durée de validité VGP (6 mois)

## Phase 2 — Fondations (back)

- [x] T004 Migrations `back/database/migrations/` : `agencies`, `machines`, `reservations`
- [x] T005 Modèles `back/app/Models/Agency.php`, `Machine.php`, `Reservation.php`
- [x] T006 Seeder `back/database/seeders/ValletSeeder.php` avec les données exactes de la spec
- [x] T007 Enum `back/app/Enums/Violation.php` et classe `back/app/Services/ReservationRules.php` (R1, R2, R3)

## Phase 3 — US1 Rechercher (P1)

- [x] T008 `GET /api/agencies`, `/api/machine-types`, `/api/machines` avec disponibilité et motifs
- [x] T009 Test `back/tests/Feature/SearchMachinesTest.php` (test spec n°2)

## Phase 4 — US2 Réserver (P1)

- [x] T010 `back/app/Http/Requests/StoreReservationRequest.php` (R4, R5, R6) et `POST /api/reservations` (R1, R2, R3, R7)
- [x] T011 `GET /api/reservations`
- [x] T012 Test `back/tests/Feature/CreateReservationTest.php` (tests spec n°1, 3, 4 et cas limites)

## Phase 5 — US3 Anomalies, US4 Annuler, US5 Atelier (P2)

- [x] T013 `GET /api/anomalies` et test `back/tests/Feature/AnomaliesTest.php` (test spec n°5)
- [x] T014 `DELETE /api/reservations/{id}` et test
- [x] T015 `PATCH /api/machines/{ref}/workshop` et `/vgp` et test

## Phase 6 — Web

- [x] T016 `web/app/app.vue` : sélecteur d'agence et onglets
- [x] T017 Onglet Rechercher et réserver
- [x] T018 Onglets Planning, Anomalies, Atelier

## Phase 7 — Recette

- [x] T019 Lancer `php artisan test` dans `back/`
- [x] T020 Jouer les 5 tests de la spec sur le prototype dans le navigateur
