# Implementation Plan: Réservation inter-agences

**Branch**: `001-reservation-inter-agences` | **Date**: 2026-10-09 (mis à jour après la deuxième recette) | **Spec**: [spec.md](spec.md)

## Summary

Un back Laravel expose une API JSON qui porte toutes les règles métier (disponibilité, atelier, VGP, transfert, jour de nettoyage, grands comptes, délai d'annulation, droits par profil). Un front Nuxt d'une page consomme cette API. Les règles vivent uniquement côté back : le front affiche les motifs et les droits renvoyés, il ne les recalcule jamais. Les données des consignes sont chargées par un seeder.

## Affected Repos

- `back` — Laravel 13, PHP 8.4, SQLite, Sanctum, PHPUnit
- `web` — Nuxt 4, TypeScript

Les deux vivent dans le même dépôt `Mr-Vallet` (choix du binôme, écart assumé à la convention multi-dépôts de spec-kit).

## Technical Context

- **Langages** : PHP 8.4 (back), TypeScript (web)
- **Stockage** : SQLite, base reconstruite et seedée à chaque démarrage du conteneur `back` (`migrate:fresh --seed`)
- **Tests** : PHPUnit, au moins un test de feature par règle et par test de la spec (106 tests)
- **Exécution** : Docker Compose (`back` sur le port 8000, `web` sur le port 3000) ; rien à installer sur le poste
- **Performance** : images Docker de production, sans partage de fichiers avec Windows (trop lent) : front Nuxt compilé (`nuxt build`), back avec OPcache et 4 processus PHP en parallèle
- **Date du jour** : simulée, `VALLET_TODAY=2026-10-12` par défaut. Toutes les dates sont inclusives.

## Data Model

- `agencies` : `id`, `name`
- `machines` : `id`, `ref` (unique), `type`, `agency_id`, `last_vgp_at` (nullable). Une machine est une nacelle soumise à la VGP quand son type commence par « Nacelle ».
- `workshop_periods` : `id`, `machine_id`, `starts_at`, `ends_at`, `reason` — passages en atelier, passés, en cours ou prévus
- `reservations` : `id`, `machine_id`, `client`, `starts_at`, `ends_at`, `entered_by_agency_id`, `purchase_order`, `cancelled_at`, `cancelled_by_agency_id`, `modified_at`, `modified_by_agency_id`
- `reservation_events` : `id`, `reservation_id`, `type` (`modified` ou `cancelled`), `occurred_on`, `user_id`, dates et bon de commande avant et après — le suivi affiché dans l'historique
- `key_accounts` : `id`, `name` (unique) — liste des grands comptes
- `users` : ajout de `role` et `agency_id` (nullable) ; `personal_access_tokens` (Sanctum)

Enums : `UserRole` (`agency`, `agency_manager`, `workshop`, `sales`, `director`), `Violation` (`overlap`, `workshop`, `vgp_expired`, `transfer`, `missing_purchase_order`), `ReservationStatus` (`upcoming`, `ongoing`, `finished`, `cancelled`), `ReservationEventType`.

## Services (back)

- `ReservationRules` : renvoie la liste des violations pour une machine et une période, en ignorant éventuellement une réservation (cas de la modification).
  - chevauchement, y compris le jour du retour (nettoyage, carte 3) ;
  - atelier : chevauchement avec un `workshop_period` ;
  - VGP : nacelle et VGP absente ou expirée avant la fin de la location ;
  - transfert : machine d'une autre agence, la veille du départ doit être libre (carte 1) ;
  - bon de commande : client de la liste des grands comptes sans numéro (carte 2), comparaison sans tenir compte des majuscules.
- `ReservationPermissions` : qui peut annuler ou modifier une réservation, avec le motif du refus.
  - annuler : responsable de l'agence de saisie ou de l'agence de la machine, ou Direction ; jamais un agent ni la commerciale ;
  - modifier : en plus, les agents de l'agence de saisie ;
  - délai : pas d'annulation à moins de 48 h du début (carte 4), la Direction en est exemptée tant que la location n'est pas terminée (deuxième recette) ; pas de raccourcissement ni de décalage à moins de 48 h, la prolongation reste permise.
- `MachineTimeline` : pour chaque machine, ce qui l'occupe (réservations, atelier) et ses créneaux libres sur 60 jours, en appliquant les mêmes règles que la réservation (transfert compris).

La recherche, la réservation, la modification, la frise et les anomalies passent toutes par `ReservationRules`.

## API

Toutes les routes sauf `login` sont sous `auth:sanctum`. Un refus de règle renvoie 422 avec les motifs ; un refus de droit renvoie 403 avec un message.

| Méthode | Route | Rôle | Profils |
|---|---|---|---|
| POST | `/api/login` | jeton Sanctum (limité à 10 essais par minute) | tous |
| GET | `/api/me` | profil, agence, droits (`can_book`, `chooses_entering_agency`…) | tous |
| POST | `/api/logout` | révoque le jeton | tous |
| GET | `/api/agencies` | les 7 agences | tous |
| GET | `/api/key-accounts` | liste des grands comptes | tous |
| POST / DELETE | `/api/key-accounts`, `/api/key-accounts/{id}` | ajouter, retirer un grand compte | commerciale, Direction |
| GET | `/api/clients` | clients déjà connus, pour la saisie | tous |
| GET | `/api/machine-types` | types de machines | tous |
| GET | `/api/machines?type=&from=&to=` | parc, disponibilité, motifs et frise par machine | tous |
| POST | `/api/machines/{ref}/workshop-periods` | déclarer un passage en atelier | atelier, Direction |
| DELETE | `/api/workshop-periods/{id}` | supprimer un passage en atelier | atelier, Direction |
| PATCH | `/api/machines/{ref}/vgp` | mettre à jour la VGP | atelier, Direction |
| GET | `/api/reservations` | planning (réservations actives) avec les droits du profil sur chacune | tous |
| GET | `/api/reservation-history` | toutes les réservations avec statut et suivi | tous |
| POST | `/api/reservations` | réserver | agents, responsables, Direction |
| PATCH | `/api/reservations/{id}` | modifier (dates, bon de commande) ; trace dans le suivi | voir `ReservationPermissions` |
| DELETE | `/api/reservations/{id}` | annuler ; trace dans le suivi | voir `ReservationPermissions` |
| GET | `/api/anomalies` | réservations existantes qui violent une règle | tous |

Chaque réservation renvoyée porte `can_cancel`, `can_modify`, `cancel_denied_reason` et `cancel_beyond_deadline` : le front affiche ou grise les boutons sans recalculer.

## Connexion et profils (US6, US13)

- Laravel Sanctum, jetons d'API. Comptes de démonstration créés par le seeder : un agent et un responsable par agence, l'atelier, la commerciale, la Direction. Identifiants listés dans le README.
- L'agence de saisie est celle du compte ; la Direction la choisit à chaque réservation.
- Web : jeton partagé dans un état Nuxt et un cookie, ajouté en en-tête `Authorization` sur chaque appel ; écran de connexion tant qu'il n'y a pas de jeton valide ; onglets et boutons masqués selon le profil.

## Web

Une page à onglets, affichés selon le profil :

- **Rechercher et réserver** : filtres nom de machine, type, période ; disponibilité et motifs ; frise « À venir » avec créneaux libres ; formulaire de réservation (bon de commande demandé pour un grand compte, agence de saisie pour la Direction).
- **Planning** : vue frise (2 semaines, doubles réservations superposées, ligne du jour) ou liste ; filtres nom de machine, agence, client, saisie par, période ; panneau de détail au clic sur une réservation ou un passage en atelier, avec modifier et annuler selon les droits.
- **Historique** : toutes les réservations, statut, suivi des modifications et annulations ; mêmes filtres.
- **Anomalies** : doubles réservations, VGP expirées, grands comptes sans bon de commande, réservations en conflit avec l'atelier.
- **Grands comptes** : gestion de la liste (commerciale, Direction).
- **Atelier** : passages en atelier (dates de début et de fin, prévus), VGP, filtres nom et type, détail au clic.

Composables : `useApiFetch` et `useFirstLoad` (chargement affiché seulement au premier affichage), `useActionNotice` (message de confirmation après une action), `useReservationFilters`. Plugin client : ouverture du sélecteur de date au clic. Les listes se rechargent après chaque action sans perdre la position.

## Hors périmètre

Voir « Ce que l'outil ne fait pas ce matin » dans la spec.
