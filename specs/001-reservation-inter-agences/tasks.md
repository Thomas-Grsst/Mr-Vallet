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

## Phase 6b — US6 Connexion (P1)

- [x] T021 Installer Sanctum dans `back/`, migration `role` et `agency_id` sur `users`, enum `back/app/Enums/UserRole.php`
- [x] T022 `back/app/Http/Controllers/AuthController.php` (login, logout, me) et routes protégées par `auth:sanctum`
- [x] T023 Contrôles de profil sur réserver, annuler, atelier, VGP ; agence de saisie prise du compte
- [x] T024 Comptes de démonstration dans `back/database/seeders/ValletSeeder.php`, identifiants dans `README.md`
- [x] T025 Test `back/tests/Feature/AuthenticationTest.php` (scénarios US6) et adaptation des tests existants
- [x] T026 `web/` : écran de connexion, jeton en cookie, déconnexion, onglets et boutons selon le profil

## Phase 7 — Recette

- [x] T019 Lancer `php artisan test` dans `back/`
- [x] T020 Jouer les 5 tests de la spec sur le prototype dans le navigateur

## Phase 8 — Retours de Brice (première recette)

- [x] T027 Bouton « Réserver à d'autres dates » sur une machine indisponible (US2)
- [x] T028 Performance : images Docker de production, OPcache, 4 processus PHP, sans partage de fichiers Windows
- [x] T029 Avertissement d'anomalie dans le planning, filtres par paramètre (nom, agence, client, saisie par, période) via `web/app/composables/useReservationFilters.ts` et `web/app/components/ReservationFilters.vue`
- [x] T030 Messages de chargement (`useFirstLoad`, `LoadingMessage.vue`) : jamais de faux « Aucune anomalie » (tests 11, 12)
- [x] T031 Table `workshop_periods`, `POST /api/machines/{ref}/workshop-periods`, `DELETE /api/workshop-periods/{id}`, onglet Atelier (début, fin, prévus, filtres, « en retard » en premier), « Mettre à jour la VGP » ; test `back/tests/Feature/WorkshopAndVgpTest.php` (remplace T015)
- [x] T032 `GET /api/reservation-history`, enum `ReservationStatus`, onglet Historique ; test `back/tests/Feature/HistoryTest.php` (US11)
- [x] T033 Profil Direction (`director`) : tous les onglets, choix de l'agence de saisie ; test `back/tests/Feature/DirectorTest.php`
- [x] T034 Toutes les machines affichées dès l'ouverture de la recherche ; identifiants de démonstration dans `README.md`

## Phase 9 — Cartes révélation

- [x] T035 Carte 1, transfert : `ReservationRules::transfer`, la veille doit être libre ; test `back/tests/Feature/TransferTest.php` (US7)
- [x] T036 Frise par machine et créneaux libres sur 60 jours : `back/app/Services/MachineTimeline.php`, `web/app/components/MachineTimeline.vue` ; test `back/tests/Feature/MachineTimelineTest.php` (US12)
- [x] T037 Carte 2, grands comptes : table `key_accounts`, colonne `purchase_order`, `ReservationRules::purchaseOrder`, routes `key-accounts` et `clients`, onglet Grands comptes ; tests `PurchaseOrderTest.php`, `KeyAccountManagementTest.php` (US8)
- [x] T038 Planning en frise (`PlanningTimeline.vue`) et panneaux de détail (`ReservationDetail.vue`, `WorkshopPeriodDetail.vue`) (US10)
- [x] T039 Carte 3, jour de nettoyage : le jour du retour est occupé ; test `back/tests/Feature/CleaningDayTest.php` (US7)
- [x] T040 Carte 4, délai d'annulation de 48 h (version du binôme) : `Reservation::cancellableUntil`, `isCancellableOn` ; test `back/tests/Feature/CancellationDeadlineTest.php`
- [x] T041 Modifier une réservation : `PATCH /api/reservations/{id}`, `UpdateReservationRequest`, pas de raccourcissement ni de décalage à moins de 48 h ; test `back/tests/Feature/UpdateReservationTest.php` (US9)
- [x] T042 Carte 5, responsables d'agence : rôle `agency_manager`, un compte par agence, `back/app/Services/ReservationPermissions.php` (annuler, modifier, motifs de refus) ; test `back/tests/Feature/AgencyManagerTest.php` (US13)
- [x] T043 Suivi des modifications et annulations : table `reservation_events`, `ReservationEvent::describe()`, affichage dans l'historique ; test `back/tests/Feature/ReservationEventsTest.php` (US11)

## Phase 10 — Deuxième recette de Brice

- [x] T044 Détail au clic dans l'onglet Atelier
- [x] T045 La Direction annule même à moins de 48 h, tant que la location n'est pas terminée (`ReservationPermissions::ignoresCancellationDeadline`)
- [x] T046 Listes rechargées après chaque action sans perdre la position, message de confirmation (`useActionNotice`, `ActionNotice.vue`) (US14)
- [x] T047 Date du jour proposée par défaut pour la VGP ; sélecteur de date ouvert au clic (plugin client)
- [x] T048 Recherche par nom de machine dans Rechercher et réserver, Planning et Historique

## Phase 11 — Recette finale

- [x] T049 `php artisan test` dans `back/` : 106 tests passent
- [x] T050 Les 39 tests de la spec joués sur le prototype dans le navigateur, données réinitialisées ensuite
- [x] T051 Spec, plan, tâches, checklist et constitution remis à jour
