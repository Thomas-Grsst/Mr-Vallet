# Feature Specification: Réservation inter-agences

**Feature Branch**: `001-reservation-inter-agences`

**Created**: 2026-10-09

**Status**: Draft

**Input**: Brice Vallet valide la première étape : remettre de l'ordre dans les réservations des 7 agences. Il veut un outil qui permet de chercher une machine dans les 7 agences et de la réserver sans erreur. Sources : dossier Vallet (DOC 1 à 6), fiche besoin du 08/10, consignes du 09/10.

**Date de référence du prototype** : lundi 12 octobre 2026 (« aujourd'hui » dans l'outil).

## Le problème

Chaque agence tient son propre Excel de planning : personne ne connaît la disponibilité réelle du parc, d'où une dizaine de doubles réservations par mois rien qu'à Lyon Est (DOC 2) et des nacelles réservées avec une VGP échue (DOC 3, DOC 5).

## Les utilisateurs

- **Agences** (Sandrine Morin, Lyon Est) : cherchent une machine dans les 7 agences et la réservent pour un client.
- **Atelier** (Mehdi Arfaoui et ses techniciens) : déclarent une machine en atelier et enregistrent une nouvelle VGP.
- **Commerciale grands comptes** (Julie Ferrand) : consulte en temps réel ce qui est disponible pour ses clients.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Chercher une machine dans les 7 agences (Priority: P1)

En tant qu'agence, je veux chercher une machine par type et par période dans les 7 agences, afin de ne plus appeler les agences une par une (jusqu'à 30 minutes aujourd'hui, DOC 2).

**Why this priority**: C'est la demande explicite de Brice et la base de toute réservation fiable.

**Independent Test**: Choisir un type et une période ; la liste montre les machines disponibles de toutes les agences, et pour les autres la raison de leur indisponibilité.

**Acceptance Scenarios**:

1. **Given** MINI07 est en atelier jusqu'au 20/10, **When** une agence cherche une « Mini-pelle 1.8 t » du 15/10 au 16/10, **Then** MINI07 n'est pas disponible (motif : en atelier) et MINI12 (Saint-Etienne) est proposée.
2. **Given** NAC140 est réservée du 19/10 au 23/10, **When** on cherche une « Nacelle 12 m » du 20/10 au 21/10, **Then** NAC140 n'est pas disponible (motif : réservée par BTP Rhone).

---

### User Story 2 - Réserver sans erreur (Priority: P1)

En tant qu'agence, je veux réserver une machine pour un client, afin que la réservation soit immédiatement visible par toutes les agences ; l'outil refuse toute réservation qui viole une règle et dit pourquoi.

**Why this priority**: « Réserver sans erreur » est le critère de réussite fixé par Brice.

**Independent Test**: Réserver une machine libre (acceptée), puis tenter une réservation en conflit (refusée avec le motif).

**Acceptance Scenarios**:

1. **Given** NAC112 est réservée du 14/10 au 18/10 pour BTP Rhone, **When** Villeurbanne tente de la réserver du 16/10 au 17/10 pour Maconnerie Duclos, **Then** la réservation est refusée et l'outil indique la période occupée par BTP Rhone.
2. **Given** la dernière VGP de NAC089 date du 05/03/2026, **When** une agence tente de la réserver du 20/10 au 31/10, **Then** la réservation est bloquée avec le message « VGP non à jour, contacter l'atelier ».
3. **Given** NAC140 (Grenoble) est libre du 26/10 au 28/10 et sa VGP est à jour, **When** Lyon Est la réserve pour Facades Martin, **Then** la réservation est acceptée et apparaît dans le planning vu par toutes les agences.

---

### User Story 3 - Voir les anomalies reprises des Excel (Priority: P2)

En tant qu'utilisateur, je veux voir les réservations reprises des Excel qui violent une règle, afin de les traiter.

**Why this priority**: Les données importées contiennent déjà des erreurs (DOC 3) ; tant qu'elles ne sont pas visibles, elles se reproduisent sur le terrain.

**Independent Test**: Ouvrir la liste des anomalies au démarrage.

**Acceptance Scenarios**:

1. **Given** les réservations ont été reprises des Excel, **When** on ouvre la liste des anomalies, **Then** on voit le chevauchement NAC112 BTP Rhone / Maconnerie Duclos et la réservation NAC089 Facades Martin sur une VGP échue.

---

### User Story 4 - Annuler une réservation (Priority: P2)

En tant qu'agence, je veux annuler une réservation, afin de libérer la machine (par exemple pour résoudre une double réservation).

**Independent Test**: Annuler la réservation Maconnerie Duclos sur NAC112 ; l'anomalie disparaît.

**Acceptance Scenarios**:

1. **Given** NAC112 a deux réservations qui se chevauchent, **When** on annule celle de Maconnerie Duclos, **Then** l'anomalie de chevauchement disparaît.

---

### User Story 5 - Tenir à jour l'état atelier et la VGP (Priority: P2)

En tant qu'atelier, je veux passer une machine « en atelier » jusqu'à une date, la remettre en service, et enregistrer la date de sa dernière VGP, afin qu'on ne promette plus une machine en panne ou non conforme (DOC 5).

**Independent Test**: Enregistrer une VGP du 12/10/2026 sur NAC089 ; elle redevient réservable.

**Acceptance Scenarios**:

1. **Given** NAC089 a une VGP échue, **When** l'atelier enregistre une VGP au 12/10/2026, **Then** NAC089 peut être réservée du 20/10 au 31/10.
2. **Given** COMP21 est en service, **When** l'atelier la passe en atelier jusqu'au 25/10, **Then** elle n'est plus proposée sur une période qui commence au plus tard le 25/10.

### Edge Cases

- Réservation qui commence le jour où une autre se termine : c'est un chevauchement (dates incluses).
- Nacelle dont la VGP expire pendant la période demandée (NAC118, VGP échue le 15/10) : refusée.
- Nacelle sans date de VGP : non réservable.
- Date de fin avant la date de début, ou date de début avant le 12/10/2026 : refusée.
- Machine en atelier : réservable seulement sur une période qui commence après la date de fin d'immobilisation.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: L'outil MUST afficher le parc des 7 agences (Lyon Est, Villeurbanne, Grenoble, Saint-Etienne, Clermont-Ferrand, Annecy, Valence) à partir des données fournies.
- **FR-002**: L'outil MUST permettre de chercher les machines par type et par période, toutes agences confondues, et indiquer pour chacune si elle est disponible ou le motif de son indisponibilité.
- **FR-003 (R1)**: Une machine MUST NOT avoir deux réservations qui se chevauchent, dates incluses.
- **FR-004 (R2)**: Une machine en atelier MUST NOT être réservable sur une période qui commence au plus tard à la date de fin d'immobilisation.
- **FR-005 (R3)**: Une nacelle MUST NOT être réservable si sa VGP n'est pas valable sur toute la période ; une VGP est valable 6 mois après sa date. Une nacelle sans date de VGP n'est pas réservable.
- **FR-006 (R4)**: La date de fin MUST être égale ou postérieure à la date de début.
- **FR-007 (R5)**: La date de début MUST être égale ou postérieure à la date du jour (12/10/2026).
- **FR-008 (R6)**: Une réservation MUST indiquer le client, la machine, les dates et l'agence qui l'a saisie.
- **FR-009 (R7)**: Quand une réservation est refusée, l'outil MUST donner le motif : période occupée (avec le client et les dates), machine en atelier (avec la date de retour), ou « VGP non à jour, contacter l'atelier ».
- **FR-002b**: Dans les résultats de recherche, le bouton « Réserver » MUST être grisé et non cliquable pour une machine indisponible ; seules les machines disponibles peuvent être réservées.
- **FR-010**: L'utilisateur MUST pouvoir choisir son agence dans une liste ; elle est enregistrée comme agence de saisie.
- **FR-011**: L'outil MUST lister les réservations existantes qui violent R1 ou R3.
- **FR-012**: L'outil MUST permettre d'annuler une réservation.
- **FR-013**: L'atelier MUST pouvoir passer une machine en atelier jusqu'à une date, la remettre en service, et enregistrer une date de dernière VGP.

### Key Entities

- **Agence** : une des 7 agences.
- **Machine** : référence, type, agence de rattachement, date de dernière VGP (nacelles), en atelier jusqu'au (facultatif).
- **Réservation** : machine, client, date de début, date de fin, agence de saisie.

## Ce que l'outil ne fait pas ce matin

- Pas de réservation en ligne par les particuliers.
- Pas de tarifs, de bons de commande, de cautions ni de facturation.
- Pas de photos d'état des lieux.
- Pas de vente d'occasion.
- Pas de comptes utilisateurs ni de mots de passe.
- Pas de transfert physique de machine entre agences.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Trouver une machine disponible dans les 7 agences prend moins d'1 minute, contre jusqu'à 30 minutes aujourd'hui (DOC 2).
- **SC-002**: Zéro double réservation enregistrée par l'outil.
- **SC-003**: Zéro nacelle réservée avec une VGP non valable sur la période.
- **SC-004**: Les 5 tests de la spec passent sur le prototype.

## Nos 5 tests

1. Étant donné que NAC112 est réservée du 14/10 au 18/10 pour BTP Rhone, quand Villeurbanne tente de la réserver du 16/10 au 17/10 pour Maconnerie Duclos, alors la réservation est refusée et l'outil indique la période occupée par BTP Rhone.
2. Étant donné que MINI07 est en atelier jusqu'au 20/10, quand une agence cherche une mini-pelle 1.8 t du 15/10 au 16/10, alors MINI07 n'apparaît pas comme disponible et MINI12 (Saint-Etienne) est proposée.
3. Étant donné que la dernière VGP de NAC089 date du 05/03/2026, quand une agence tente de la réserver du 20/10 au 31/10, alors la réservation est bloquée avec le message « VGP non à jour, contacter l'atelier ».
4. Étant donné que NAC140 (Grenoble) est libre du 26/10 au 28/10 et que sa VGP est à jour, quand Lyon Est la réserve pour Facades Martin, alors la réservation est acceptée et apparaît dans le planning de toutes les agences.
5. Étant donné que les réservations ont été reprises des Excel, quand on ouvre la liste des anomalies, alors on voit le chevauchement NAC112 BTP Rhone / Maconnerie Duclos et la réservation NAC089 Facades Martin sur une VGP échue.

## Assumptions

- La périodicité VGP de 6 mois pour les nacelles vient de la fiche besoin (NAC089 : VGP 03/2026, échue en 09/2026).
- « MINI07 a l'atelier (verin casse) jusqu'au 2026-10-20 » signifie : non réservable avant le 21/10.
- Seules les nacelles sont soumises à la VGP dans les données (les autres machines n'ont pas de date).

## Données

Copiées telles quelles depuis les consignes, chargées au démarrage.

```csv
ref,type,agence,derniere_vgp,remarque
NAC112,Nacelle 12 m,Lyon Est,2026-07-10,
NAC140,Nacelle 12 m,Grenoble,2026-08-20,
NAC118,Nacelle 12 m,Annecy,2026-04-15,
NAC089,Nacelle 16 m,Lyon Est,2026-03-05,
NAC201,Nacelle 20 m,Villeurbanne,2026-09-02,
MINI07,Mini-pelle 1.8 t,Lyon Est,,a l'atelier (verin casse) jusqu'au 2026-10-20
MINI12,Mini-pelle 1.8 t,Saint-Etienne,,
MINI15,Mini-pelle 3.5 t,Clermont-Ferrand,,
COMP21,Compacteur,Lyon Est,,
COMP30,Compacteur,Annecy,,
ECH40,Echafaudage 40 m2,Lyon Est,,
ECH41,Echafaudage 40 m2,Valence,,
```

```csv
ref,client,du,au,saisie_par
NAC112,BTP Rhone,2026-10-14,2026-10-18,Lyon Est
NAC112,Maconnerie Duclos,2026-10-16,2026-10-17,Villeurbanne
NAC089,Facades Martin,2026-10-20,2026-10-31,Lyon Est
COMP21,M. Pereira (particulier),2026-10-12,2026-10-12,Lyon Est
ECH40,Constructions Alpes,2026-10-06,2026-10-24,Lyon Est
NAC140,BTP Rhone,2026-10-19,2026-10-23,Grenoble
MINI12,Artisan Ferreira,2026-10-13,2026-10-14,Saint-Etienne
```
