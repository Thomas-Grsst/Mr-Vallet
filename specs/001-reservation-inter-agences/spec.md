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

1. **Given** NAC112 est réservée du 14/10 au 18/10 pour BTP Rhone, **When** Villeurbanne cherche une « Nacelle 12 m » du 16/10 au 17/10, **Then** NAC112 apparaît indisponible avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 » et son bouton « Réserver » est grisé ; si la réservation est quand même envoyée, elle est refusée avec ce motif.
2. **Given** la dernière VGP de NAC089 date du 05/03/2026, **When** une agence cherche une « Nacelle 16 m » du 02/11 au 05/11, **Then** NAC089 apparaît indisponible avec le motif « VGP non à jour, contacter l'atelier ».
3. **Given** NAC140 (Grenoble) est libre du 26/10 au 28/10 et sa VGP est à jour, **When** Lyon Est la réserve pour Facades Martin, **Then** la réservation est acceptée et apparaît dans le planning vu par toutes les agences.
4. **Given** NAC112 est indisponible sur la recherche du 16/10 au 17/10, **When** Villeurbanne clique sur « Réserver à d'autres dates », choisit du 20/10 au 22/10 dans le formulaire et confirme pour Maconnerie Duclos, **Then** la réservation est acceptée sans avoir changé la recherche.
5. **Given** NAC112 est réservée du 14/10 au 18/10, **When** Villeurbanne choisit du 17/10 au 19/10 dans le formulaire de réservation, **Then** la réservation est refusée avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 ».

---

### User Story 3 - Voir les anomalies reprises des Excel (Priority: P2)

En tant qu'utilisateur, je veux voir les réservations reprises des Excel qui violent une règle, afin de les traiter.

**Why this priority**: Les données importées contiennent déjà des erreurs (DOC 3) ; tant qu'elles ne sont pas visibles, elles se reproduisent sur le terrain.

**Independent Test**: Ouvrir la liste des anomalies au démarrage.

**Acceptance Scenarios**:

1. **Given** les réservations ont été reprises des Excel, **When** on ouvre la liste des anomalies, **Then** on voit le chevauchement NAC112 BTP Rhone / Maconnerie Duclos et la réservation NAC089 Facades Martin sur une VGP échue.
2. **Given** NAC112 est réservée deux fois en même temps et NAC089 est réservée avec une VGP échue, **When** on ouvre le planning, **Then** ces trois réservations affichent « Attention : anomalie » sous leur machine avec le type, et les autres n'affichent rien.

---

### User Story 4 - Annuler une réservation (Priority: P2)

En tant qu'agence, je veux annuler une réservation, afin de libérer la machine (par exemple pour résoudre une double réservation).

**Independent Test**: Annuler la réservation Maconnerie Duclos sur NAC112 ; l'anomalie disparaît.

**Acceptance Scenarios**:

1. **Given** NAC112 a deux réservations qui se chevauchent, **When** on annule celle de Maconnerie Duclos, **Then** l'anomalie de chevauchement disparaît.

---

### User Story 5 - Tenir à jour l'atelier et la VGP (Priority: P2)

En tant qu'atelier, je veux déclarer ou prévoir un passage en atelier avec une date de début et de fin, remettre une machine en service, annuler un passage prévu, et enregistrer la date d'une VGP réalisée, afin qu'on ne promette plus une machine en panne, en entretien ou non conforme (DOC 5, retours de Brice).

**Independent Test**: Prévoir un passage « VGP » sur NAC201 du 02/11 au 03/11 ; elle n'est indisponible que sur ces dates.

**Acceptance Scenarios**:

1. **Given** NAC089 a une VGP échue, **When** l'atelier enregistre une VGP réalisée au 12/10/2026, **Then** NAC089 peut être réservée du 02/11 au 05/11.
2. **Given** COMP21 est en service, **When** l'atelier déclare un passage du 12/10 au 25/10, **Then** elle n'est plus proposée sur une période qui chevauche le 12/10 → 25/10.
3. **Given** NAC201 est libre, **When** l'atelier prévoit un passage « VGP » du 02/11 au 03/11, **Then** NAC201 reste réservable du 26/10 au 30/10 et est indisponible du 02/11 au 05/11 avec le motif « Machine en atelier du 02/11/2026 au 03/11/2026 (VGP) ».
4. **Given** MINI07 est en atelier du 01/10 au 20/10, **When** l'atelier la remet en service, **Then** elle est réservable dès le 12/10.
5. **Given** NAC140 est réservée par BTP Rhone du 19/10 au 23/10, **When** l'atelier déclare un passage du 20/10 au 21/10, **Then** le passage est enregistré, l'atelier voit la réservation concernée, et elle apparaît dans les anomalies.
6. **Given** COMP30 a un passage prévu du 26/10 au 27/10, **When** l'atelier en prévoit un autre du 27/10 au 28/10, **Then** c'est refusé : deux passages d'une même machine ne peuvent pas se chevaucher.

---

### User Story 6 - Se connecter (Priority: P1)

En tant qu'utilisateur, je dois me connecter avant d'utiliser l'outil, afin que personne d'extérieur ne puisse réserver, annuler ou modifier une VGP, et que chaque réservation soit attribuée à la bonne agence.

**Why this priority**: Sans connexion, toute personne qui connaît l'adresse peut modifier le planning ; la VGP engage la responsabilité légale de l'atelier (DOC 5).

**Independent Test**: Ouvrir l'outil sans être connecté : seul l'écran de connexion est accessible.

**Acceptance Scenarios**:

1. **Given** je ne suis pas connecté, **When** j'ouvre l'outil ou j'appelle une de ses fonctions, **Then** je ne vois que l'écran de connexion et rien n'est modifiable.
2. **Given** je suis connecté avec le compte de l'agence Villeurbanne, **When** je réserve une machine, **Then** la réservation est saisie par Villeurbanne, sans que je puisse choisir une autre agence.
3. **Given** je suis connecté avec un compte agence, **When** je tente d'enregistrer une VGP ou de passer une machine en atelier, **Then** c'est refusé : seul l'atelier peut le faire.
4. **Given** je suis connecté avec le compte atelier ou le compte commercial, **When** je tente de réserver ou d'annuler, **Then** c'est refusé : seules les agences réservent.
5. **Given** je saisis un mauvais mot de passe, **When** je valide, **Then** la connexion est refusée avec le message « Identifiants incorrects ».

### Edge Cases

- Réservation qui commence le jour où une autre se termine : c'est un chevauchement (dates incluses).
- Nacelle dont la VGP expire pendant la période demandée (NAC118, VGP échue le 15/10) : refusée.
- Nacelle sans date de VGP : non réservable.
- Date de fin avant la date de début, ou date de début avant le 12/10/2026 : refusée.
- Machine en atelier : réservable seulement sur une période qui ne chevauche aucun de ses passages en atelier.
- Passage en atelier déclaré sur une machine déjà réservée : accepté (une panne ne se refuse pas), la réservation devient une anomalie.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: L'outil MUST afficher le parc des 7 agences (Lyon Est, Villeurbanne, Grenoble, Saint-Etienne, Clermont-Ferrand, Annecy, Valence) à partir des données fournies.
- **FR-002**: L'outil MUST permettre de chercher les machines par type et par période, toutes agences confondues, et indiquer pour chacune si elle est disponible ou le motif de son indisponibilité.
- **FR-003 (R1)**: Une machine MUST NOT avoir deux réservations qui se chevauchent, dates incluses.
- **FR-004 (R2)**: Une machine MUST NOT être réservable sur une période qui chevauche un de ses passages en atelier, dates incluses.
- **FR-005 (R3)**: Une nacelle MUST NOT être réservable si sa dernière VGP réalisée n'est pas valable sur toute la période ; une VGP est valable 6 mois après sa date. Une nacelle sans date de VGP n'est pas réservable.
- **FR-006 (R4)**: La date de fin MUST être égale ou postérieure à la date de début.
- **FR-007 (R5)**: La date de début MUST être égale ou postérieure à la date du jour (12/10/2026).
- **FR-008 (R6)**: Une réservation MUST indiquer le client, la machine, les dates et l'agence qui l'a saisie.
- **FR-009 (R7)**: Quand une réservation est refusée, l'outil MUST donner le motif : période occupée (avec le client et les dates), machine en atelier (« Machine en atelier du … au … », avec le motif s'il existe), ou « VGP non à jour, contacter l'atelier ».
- **FR-002b**: Dans les résultats de recherche, une machine indisponible MUST afficher un bouton « Réserver à d'autres dates » à la place de « Réserver » (retour de Brice, remplace l'ancienne règle « bouton grisé »).
- **FR-002c (R9)**: Le formulaire de réservation MUST pré-remplir les dates avec la période cherchée et permettre de les modifier sans relancer la recherche ; R1 à R5 sont revérifiées à l'envoi et un refus affiche son motif (retour de Brice).
- **FR-010**: L'agence de saisie MUST être l'agence du compte connecté ; l'utilisateur ne peut pas la choisir.
- **FR-014**: Toute l'application MUST exiger une connexion par e-mail et mot de passe ; sans connexion, rien n'est consultable ni modifiable.
- **FR-015**: Il existe trois profils de compte :
  - **Agence** (un compte par agence) : rechercher, réserver, annuler, consulter le planning et les anomalies.
  - **Atelier** : rechercher, consulter, déclarer et prévoir des passages en atelier, remettre en service, annuler un passage prévu, enregistrer une VGP.
  - **Commercial** (Julie Ferrand) : rechercher et consulter uniquement.
- **FR-016**: Une action non autorisée pour le profil MUST être refusée avec un message, et le bouton correspondant n'est pas proposé.
- **FR-017**: L'utilisateur MUST pouvoir se déconnecter.
- **FR-011**: L'outil MUST lister les réservations actives qui violent R1, R2 ou R3 (double réservation, réservée pendant un passage en atelier, VGP non à jour).
- **FR-018 (R10)**: Dans le planning, chaque réservation concernée par une anomalie de FR-011 MUST afficher sous sa machine un avertissement « Attention : anomalie » avec son type (double réservation, en atelier, VGP non à jour) (retour de Brice).
- **FR-019 (R11)**: Le planning MUST pouvoir être filtré par client, choisi dans la liste des clients qui ont au moins une réservation ; « Tous les clients » réaffiche toutes les réservations (retour de Brice).
- **FR-020 (R12)**: Le planning MUST aussi pouvoir être filtré par machine, par agence de la machine, par agence de saisie et par période (réservations qui chevauchent la période) ; les filtres se cumulent, un bouton « Réinitialiser les filtres » les vide tous, et le message « Aucune réservation ne correspond aux filtres » s'affiche si rien ne correspond (initiative du binôme, non demandée par Brice).
- **FR-021 (R13)**: Pendant un chargement (onglet, recherche, réservation), l'outil MUST afficher un indicateur « Chargement… » et MUST NOT afficher « Aucune anomalie », « Aucune réservation » ni une liste vide tant que les données ne sont pas chargées ; un chargement en échec MUST afficher « Impossible de charger les données » avec un bouton « Réessayer », jamais un résultat vide (retour de Brice).
- **FR-022 (R14)**: Un onglet « Historique » MUST lister toutes les réservations, y compris annulées, avec leur statut calculé par rapport à aujourd'hui : « à venir », « en cours », « terminée », ou « annulée » avec la date et l'agence qui a annulé ; il MUST proposer les mêmes filtres que le planning plus un filtre par statut (retour de Brice).
- **FR-023 (R15)**: Annuler une réservation MUST la marquer annulée (date et agence) au lieu de l'effacer ; une réservation annulée n'apparaît plus dans le planning ni dans les anomalies et ne bloque plus la machine ; une réservation déjà annulée MUST NOT pouvoir être annulée à nouveau (retour de Brice, nécessaire à FR-022).
- **FR-012**: L'outil MUST permettre d'annuler une réservation.
- **FR-013**: L'atelier MUST pouvoir enregistrer la date d'une VGP réalisée (aujourd'hui ou avant).
- **FR-024 (R16)**: Un passage en atelier MUST avoir une date de début, une date de fin égale ou postérieure, et un motif facultatif ; le début MUST être aujourd'hui ou plus tard (sauf données reprises) ; deux passages d'une même machine MUST NOT se chevaucher (retour de Brice).
- **FR-025 (R17)**: L'atelier MUST pouvoir prévoir un passage dans le futur (par exemple une VGP) ; une VGP prévue MUST NOT compter comme réalisée pour FR-005 (retour de Brice).
- **FR-026 (R18)**: Un passage en cours MUST pouvoir être terminé (« Remettre en service » : la machine est disponible dès aujourd'hui) et un passage prévu MUST pouvoir être annulé (« Annuler ce passage ») (retour de Brice).
- **FR-027 (R19)**: Un passage en atelier MAY chevaucher une réservation existante ; l'atelier MUST alors voir la liste des réservations concernées, et chacune MUST apparaître dans les anomalies (conséquence de FR-024).
- **FR-028 (R20)**: L'onglet Atelier MUST pouvoir être filtré par nom de machine (saisie libre, correspondance partielle, sans tenir compte des majuscules) et par type de machine (liste) ; les filtres se cumulent et « Aucune machine ne correspond » s'affiche si rien ne correspond (retour de Brice).
- **FR-029 (R21)**: Dans l'onglet Atelier, l'état VGP d'une nacelle MUST être écrit en toutes lettres : « VGP en retard » si elle n'est plus valable aujourd'hui ou absente, sinon « VGP à jour », suivi de la date de la dernière VGP réalisée et de sa fin de validité ; la couleur seule ne suffit pas (retour de Brice).

### Key Entities

- **Agence** : une des 7 agences.
- **Machine** : référence, type, agence de rattachement, date de dernière VGP réalisée (nacelles).
- **Passage en atelier** : machine, date de début, date de fin, motif (facultatif) ; en cours ou prévu selon la date du jour.
- **Réservation** : machine, client, date de début, date de fin, agence de saisie, date d'annulation et agence qui a annulé (si annulée).
- **Compte** : nom, e-mail, mot de passe, profil (agence, atelier, commercial), agence (pour le profil agence).

## Ce que l'outil ne fait pas ce matin

- Pas de réservation en ligne par les particuliers.
- Pas de tarifs, de bons de commande, de cautions ni de facturation.
- Pas de photos d'état des lieux.
- Pas de vente d'occasion.
- Pas de gestion des comptes : les comptes de démonstration sont créés au démarrage, sans création de compte, mot de passe oublié ni changement de mot de passe.
- Sécurité limitée à la connexion : prototype en local, sans HTTPS ; à renforcer avant toute mise en service.
- Question ouverte pour Brice : une agence peut-elle annuler une réservation saisie par une autre agence ? Ce matin, oui.
- L'historique ne montre que les réservations, pas les actions de l'atelier (mises en atelier, VGP) : question à poser à Brice.
- Une VGP prévue ne débloque pas la nacelle à l'avance : question à poser à Brice.
- Pas de transfert physique de machine entre agences.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: Trouver une machine disponible dans les 7 agences prend moins d'1 minute, contre jusqu'à 30 minutes aujourd'hui (DOC 2).
- **SC-002**: Zéro double réservation enregistrée par l'outil.
- **SC-003**: Zéro nacelle réservée avec une VGP non valable sur la période.
- **SC-004**: Les 5 tests de la spec passent sur le prototype.

## Nos 5 tests

Identiques à `SPEC.md` à la racine (version une page au format des consignes).

1. Étant donné que NAC112 est réservée du 14/10 au 18/10 pour BTP Rhone, quand Villeurbanne cherche une « Nacelle 12 m » du 16/10 au 17/10, alors NAC112 apparaît indisponible avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 » et son bouton affiche « Réserver à d'autres dates ».
2. Étant donné que MINI07 est en atelier du 01/10 au 20/10, quand une agence cherche une « Mini-pelle 1.8 t » du 15/10 au 16/10, alors MINI07 apparaît indisponible (en atelier) et MINI12 (Saint-Etienne) est proposée.
3. Étant donné que la dernière VGP de NAC089 date du 05/03/2026, quand une agence cherche une « Nacelle 16 m » du 02/11 au 05/11, alors NAC089 apparaît indisponible avec le motif « VGP non à jour, contacter l'atelier ».
4. Étant donné que NAC140 (Grenoble) est libre du 26/10 au 28/10 et que sa VGP est à jour, quand Lyon Est la réserve pour Facades Martin, alors la réservation est acceptée et apparaît dans le planning, saisie par Lyon Est.
5. Étant donné que les réservations ont été reprises des Excel, quand on ouvre l'onglet Anomalies, alors on voit la double réservation NAC112 (BTP Rhone / Maconnerie Duclos) et NAC089 réservée pour Facades Martin avec une VGP échue.

Tests ajoutés après la recette de Brice :

6. Étant donné que NAC112 est indisponible sur la recherche du 16/10 au 17/10, quand Villeurbanne clique sur « Réserver à d'autres dates », choisit du 20/10 au 22/10 dans le formulaire et confirme pour Maconnerie Duclos, alors la réservation est acceptée sans avoir changé la recherche.
7. Étant donné que NAC112 est réservée du 14/10 au 18/10, quand Villeurbanne choisit du 17/10 au 19/10 dans le formulaire de réservation, alors la réservation est refusée avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 ».
8. Étant donné que NAC112 est réservée deux fois en même temps (BTP Rhone et Maconnerie Duclos) et que NAC089 est réservée pour Facades Martin avec une VGP échue, quand on ouvre le planning, alors ces trois réservations affichent « Attention : anomalie » sous leur machine (double réservation pour les deux NAC112, VGP non à jour pour NAC089), et les autres réservations n'affichent rien.
9. Étant donné que BTP Rhone a réservé NAC112 du 14/10 au 18/10 et NAC140 du 19/10 au 23/10, quand on choisit le client « BTP Rhone » dans le filtre du planning, alors seules ces deux réservations s'affichent.
10. Étant donné que BTP Rhone a réservé NAC112 (Lyon Est) et NAC140 (Grenoble), quand on filtre le planning sur le client « BTP Rhone » et l'agence de la machine « Lyon Est », alors seule la réservation NAC112 du 14/10 au 18/10 s'affiche ; quand on filtre seulement sur la période du 19/10 au 19/10, alors s'affichent ECH40 (Constructions Alpes) et NAC140 (BTP Rhone).
11. Étant donné que les anomalies mettent du temps à arriver, quand on ouvre l'onglet Anomalies, alors on voit « Chargement des anomalies… » et jamais « Aucune anomalie » tant qu'elles ne sont pas arrivées.
12. Étant donné que le serveur ne répond pas, quand on ouvre l'onglet Anomalies, alors on voit « Impossible de charger les données » avec un bouton « Réessayer », et pas « Aucune anomalie ».
13. Étant donné qu'aujourd'hui est le 12/10/2026, quand on ouvre l'historique, alors ECH40 (Constructions Alpes, 06/10 → 24/10) et COMP21 (M. Pereira, 12/10 → 12/10) sont « en cours », et NAC112 (BTP Rhone, 14/10 → 18/10) est « à venir ».
14. Étant donné que Lyon Est annule la réservation NAC112 de Maconnerie Duclos, quand on ouvre l'historique et qu'on filtre sur le statut « annulée », alors on voit cette réservation « annulée le 12/10/2026 par Lyon Est » ; elle n'apparaît plus dans le planning ni dans les anomalies.
15. Étant donné que NAC201 (Villeurbanne) est libre, quand l'atelier prévoit un passage « VGP » du 02/11 au 03/11, alors l'onglet Atelier affiche ce passage « prévu du 02/11/2026 au 03/11/2026 », NAC201 reste réservable du 26/10 au 30/10, et une recherche du 02/11 au 05/11 la montre indisponible avec le motif « Machine en atelier du 02/11/2026 au 03/11/2026 (VGP) ».
16. Étant donné que MINI07 est en atelier du 01/10 au 20/10, quand l'atelier clique sur « Remettre en service », alors MINI07 devient réservable dès le 12/10 ; et quand l'atelier annule un passage prévu, la machine redevient réservable sur cette période.
17. Étant donné que NAC140 est réservée par BTP Rhone du 19/10 au 23/10, quand l'atelier déclare un passage du 20/10 au 21/10 (« panne moteur »), alors le passage est enregistré, l'atelier voit « Réservation concernée : BTP Rhone du 19/10/2026 au 23/10/2026 », et la réservation apparaît dans les anomalies comme « réservée pendant un passage en atelier ».
18. Étant donné le parc de 12 machines, quand l'atelier tape « nac1 » dans le filtre par nom, alors seules NAC112, NAC118 et NAC140 s'affichent ; quand il choisit en plus le type « Nacelle 12 m », les trois restent ; quand il choisit seulement le type « Compacteur », alors seules COMP21 et COMP30 s'affichent ; quand il tape « XYZ », alors « Aucune machine ne correspond » s'affiche.
19. Étant donné qu'aujourd'hui est le 12/10/2026 et que la VGP de NAC089 était valable jusqu'au 05/09/2026, quand l'atelier ouvre l'onglet Atelier, alors NAC089 affiche « VGP en retard · dernière VGP 05/03/2026, valable jusqu'au 05/09/2026 », et NAC112 affiche « VGP à jour · dernière VGP 10/07/2026, valable jusqu'au 10/01/2027 ».

## Assumptions

- La périodicité VGP de 6 mois pour les nacelles vient de la fiche besoin (NAC089 : VGP 03/2026, échue en 09/2026).
- « MINI07 a l'atelier (verin casse) jusqu'au 2026-10-20 » devient un passage du 01/10/2026 au 20/10/2026 : DOC 5, envoyé le mardi 06/10, dit « chez nous depuis jeudi », soit le 01/10.
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
