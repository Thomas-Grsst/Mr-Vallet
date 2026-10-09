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
- **Atelier** (Mehdi Arfaoui et ses techniciens) : déclarent et prévoient les passages en atelier, et mettent à jour les VGP.
- **Commerciale grands comptes** (Julie Ferrand) : consulte en temps réel ce qui est disponible pour ses clients.

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Chercher une machine dans les 7 agences (Priority: P1)

En tant qu'agence, je veux chercher une machine par type et par période dans les 7 agences, afin de ne plus appeler les agences une par une (jusqu'à 30 minutes aujourd'hui, DOC 2).

**Why this priority**: C'est la demande explicite de Brice et la base de toute réservation fiable.

**Independent Test**: Choisir un type et une période ; la liste montre les machines disponibles de toutes les agences, et pour les autres la raison de leur indisponibilité.

**Acceptance Scenarios**:

1. **Given** MINI07 est en atelier jusqu'au 20/10, **When** Lyon Est cherche une « Mini-pelle 1.8 t » du 16/10 au 17/10, **Then** MINI07 n'est pas disponible (motif : en atelier) et MINI12 (Saint-Etienne) est proposée.
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

En tant qu'atelier, je veux déclarer ou prévoir un passage en atelier avec une date de début et de fin, remettre une machine en service, annuler un passage prévu, et mettre à jour la VGP avec la date de la VGP réalisée, afin qu'on ne promette plus une machine en panne, en entretien ou non conforme (DOC 5, retours de Brice).

**Independent Test**: Prévoir un passage « VGP » sur NAC201 du 02/11 au 03/11 ; elle n'est indisponible que sur ces dates.

**Acceptance Scenarios**:

1. **Given** NAC089 a une VGP échue, **When** l'atelier met à jour la VGP au 12/10/2026, **Then** NAC089 peut être réservée du 02/11 au 05/11.
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
3. **Given** je suis connecté avec un compte agence, **When** je tente de mettre à jour la VGP ou de passer une machine en atelier, **Then** c'est refusé : seul l'atelier peut le faire.
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
- **FR-010**: L'agence de saisie MUST être l'agence du compte connecté ; un compte agence ne peut pas la choisir ; le compte Direction MUST la choisir dans le formulaire (FR-032).
- **FR-014**: Toute l'application MUST exiger une connexion par e-mail et mot de passe ; sans connexion, rien n'est consultable ni modifiable.
- **FR-015**: Il existe quatre profils de compte :
  - **Agence** (un compte par agence) : rechercher, réserver, annuler, consulter le planning et les anomalies.
  - **Atelier** : rechercher, consulter, déclarer et prévoir des passages en atelier, remettre en service, annuler un passage prévu, mettre à jour la VGP.
  - **Commercial** (Julie Ferrand) : rechercher et consulter, et tenir la liste des grands comptes (FR-037).
  - **Direction** (Brice Vallet) : tous les droits des trois autres profils (FR-032).
- **FR-016**: Une action non autorisée pour le profil MUST être refusée avec un message, et le bouton correspondant n'est pas proposé.
- **FR-017**: L'utilisateur MUST pouvoir se déconnecter.
- **FR-011**: L'outil MUST lister les réservations actives qui violent R1, R2, R3 ou R28 (double réservation, réservée pendant un passage en atelier, VGP non à jour, grand compte sans bon de commande).
- **FR-018 (R10)**: Dans le planning, chaque réservation concernée par une anomalie de FR-011 MUST afficher sous sa machine un avertissement « Attention : anomalie » avec son type (double réservation, en atelier, VGP non à jour, sans bon de commande) (retour de Brice).
- **FR-019 (R11)**: Le planning MUST pouvoir être filtré par client, choisi dans la liste des clients qui ont au moins une réservation ; « Tous les clients » réaffiche toutes les réservations (retour de Brice).
- **FR-020 (R12)**: Le planning MUST aussi pouvoir être filtré par machine, par agence de la machine, par agence de saisie et par période (réservations qui chevauchent la période) ; les filtres se cumulent, un bouton « Réinitialiser les filtres » les vide tous, et le message « Aucune réservation ne correspond aux filtres » s'affiche si rien ne correspond (initiative du binôme, non demandée par Brice).
- **FR-021 (R13)**: Pendant un chargement (onglet, recherche, réservation), l'outil MUST afficher un indicateur « Chargement… » et MUST NOT afficher « Aucune anomalie », « Aucune réservation » ni une liste vide tant que les données ne sont pas chargées ; un chargement en échec MUST afficher « Impossible de charger les données » avec un bouton « Réessayer », jamais un résultat vide (retour de Brice).
- **FR-022 (R14)**: Un onglet « Historique » MUST lister toutes les réservations, y compris annulées, avec leur statut calculé par rapport à aujourd'hui : « à venir », « en cours », « terminée », ou « annulée » avec la date et l'agence qui a annulé ; il MUST proposer les mêmes filtres que le planning plus un filtre par statut (retour de Brice).
- **FR-023 (R15)**: Annuler une réservation MUST la marquer annulée (date et agence) au lieu de l'effacer ; une réservation annulée n'apparaît plus dans le planning ni dans les anomalies et ne bloque plus la machine ; une réservation déjà annulée MUST NOT pouvoir être annulée à nouveau (retour de Brice, nécessaire à FR-022).
- **FR-012**: L'outil MUST permettre d'annuler une réservation.
- **FR-013**: L'atelier MUST pouvoir mettre à jour la VGP avec la date de la VGP réalisée (aujourd'hui ou avant).
- **FR-024 (R16)**: Un passage en atelier MUST avoir une date de début, une date de fin égale ou postérieure, et un motif facultatif ; le début MUST être aujourd'hui ou plus tard (sauf données reprises) ; deux passages d'une même machine MUST NOT se chevaucher (retour de Brice).
- **FR-025 (R17)**: L'atelier MUST pouvoir prévoir un passage dans le futur (par exemple une VGP) ; une VGP prévue MUST NOT compter comme réalisée pour FR-005 (retour de Brice).
- **FR-026 (R18)**: Un passage en cours MUST pouvoir être terminé (« Remettre en service » : la machine est disponible dès aujourd'hui) et un passage prévu MUST pouvoir être annulé (« Annuler ce passage ») (retour de Brice).
- **FR-027 (R19)**: Un passage en atelier MAY chevaucher une réservation existante ; l'atelier MUST alors voir la liste des réservations concernées, et chacune MUST apparaître dans les anomalies (conséquence de FR-024).
- **FR-028 (R20)**: L'onglet Atelier MUST pouvoir être filtré par nom de machine (saisie libre, correspondance partielle, sans tenir compte des majuscules) et par type de machine (liste) ; les filtres se cumulent et « Aucune machine ne correspond » s'affiche si rien ne correspond (retour de Brice).
- **FR-029 (R21)**: Dans l'onglet Atelier, l'état VGP d'une nacelle MUST être écrit en toutes lettres : « VGP en retard » si elle n'est plus valable aujourd'hui ou absente, sinon « VGP à jour », suivi de la date de la dernière VGP réalisée et de sa fin de validité ; la couleur seule ne suffit pas (retour de Brice).
- **FR-030 (R22)**: Dans l'onglet Atelier, les machines « VGP en retard » MUST apparaître en premier, puis les autres par type puis par nom ; l'ordre MUST être conservé avec les filtres (retour de Brice).
- **FR-031 (R23)**: Dans l'onglet Atelier, l'action sur la VGP MUST s'appeler « Mettre à jour la VGP » (retour de Brice, remplace « Enregistrer la VGP »).
- **FR-032 (R24)**: Un compte « Direction » (Brice Vallet) MUST voir tous les onglets et avoir tous les droits : réserver, annuler, gérer les passages en atelier, mettre à jour la VGP ; il MUST choisir l'agence de saisie dans le formulaire de réservation (refus sans agence) ; ses annulations MUST apparaître « par Direction » dans l'historique (retour de Brice).
- **FR-033 (R25)**: En arrivant sur l'onglet « Rechercher », la recherche MUST être lancée automatiquement sur tous les types pour aujourd'hui, pour afficher tout le parc avec sa disponibilité du jour (initiative du binôme, non demandée par Brice).
- **FR-034 (R26)**: Quand l'agence qui réserve n'est pas l'agence de la machine, la machine MUST aussi être libre la veille du départ (ni réservée, ni en atelier) ; sinon la réservation MUST être refusée avec « Transfert depuis <agence> impossible : la machine doit être libre la veille du départ (<date>) » suivi de ce qui l'occupe. La recherche d'un compte agence MUST appliquer la même règle ; le compte Direction y est soumis à la réservation, pour l'agence choisie (carte révélation 1).
- **FR-035 (R27)**: Dans les résultats de recherche, chaque machine MUST afficher, sur les 60 prochains jours, ce qui l'occupe (réservations avec client, passages en atelier avec motif, date d'échéance VGP) et ses créneaux libres ; un créneau libre MUST être calculé avec les mêmes règles que la réservation pour l'agence connectée, de sorte que toute réservation comprise dans ce créneau soit acceptée ; « Libre à partir du … » s'affiche si le créneau dépasse l'horizon (initiative du binôme, non demandée par Brice).
- **FR-036 (R28)**: Une réservation pour un grand compte MUST avoir un numéro de bon de commande. L'outil MUST connaître la liste des grands comptes (au départ : BTP Rhone, DOC 6) et la reconnaître sans tenir compte des majuscules ni des espaces autour, en enregistrant le client sous son nom de la liste ; sans numéro, la réservation MUST être refusée avec « <client> est un grand compte : le numéro de bon de commande est obligatoire ». Le numéro MUST s'afficher dans le planning et l'historique ; une réservation de grand compte existante sans numéro MUST apparaître dans les anomalies (carte révélation 2).
- **FR-037 (R29)**: Un onglet « Grands comptes » MUST afficher la liste ; la Direction et la commerciale grands comptes MUST pouvoir ajouter une entreprise (nom obligatoire, sans doublon quelles que soient les majuscules) ou en retirer une ; les autres profils la consultent. Le champ « Client » du formulaire de réservation MUST proposer les clients connus et les grands comptes, marqués « (grand compte) » (conséquence de la carte révélation 2).
- **FR-038 (R30)**: Le planning MUST s'afficher par défaut en frise : une ligne par machine, une colonne par jour, une barre par réservation (rouge avec « ⚠ » si elle a une anomalie), les passages en atelier et la VGP échue sur la ligne de la machine, les réservations qui se chevauchent l'une sous l'autre ; durée affichée au choix (1 semaine, 2 semaines par défaut, 1 mois) avec navigation et retour à aujourd'hui ; filtres R11/R12 appliqués ; bouton « Liste » pour le tableau (initiative du binôme).
- **FR-039 (R31)**: Un clic sur une réservation (frise ou liste) MUST ouvrir un panneau de détail : machine, client, bon de commande, dates, agence de saisie, anomalies, et « Annuler la réservation » pour les profils qui réservent, si elle est encore annulable (FR-041) (initiative du binôme).
- **FR-040 (R32)**: Une machine MUST NOT être relouée le jour de son retour (jour de fin d'une réservation), car elle est nettoyée et contrôlée ; une nouvelle location commence au plus tôt le lendemain (assuré par les dates incluses de FR-003, qui MUST le rester). Le refus MUST expliquer « Retour de <client> le <date> : la machine est nettoyée et contrôlée ce jour-là, elle est relouable à partir du <lendemain> » (carte révélation 3).
- **FR-041 (R33)**: Une réservation MUST être annulable seulement jusqu'à 48 h avant son début (au plus tard 2 jours avant la date de début) ; ensuite l'annulation MUST être refusée avec « Annulation impossible : la location commence le <date>, il fallait annuler au plus tard le <date − 2 jours>. Le client doit garder la réservation. », pour tous les comptes. Le panneau de détail MUST indiquer « Annulable jusqu'au <date> » ou « Plus annulable (moins de 48 h avant le début) » et ne proposer le bouton que si l'annulation est possible (carte révélation 4, précisée par Brice).

### Key Entities

- **Agence** : une des 7 agences.
- **Machine** : référence, type, agence de rattachement, date de dernière VGP réalisée (nacelles).
- **Passage en atelier** : machine, date de début, date de fin, motif (facultatif) ; en cours ou prévu selon la date du jour.
- **Réservation** : machine, client, n° de bon de commande (grands comptes), date de début, date de fin, agence de saisie, date d'annulation et agence qui a annulé (si annulée).
- **Compte** : nom, e-mail, mot de passe, profil (agence, atelier, commercial, direction), agence (pour le profil agence).

## Ce que l'outil ne fait pas ce matin

- Pas de réservation en ligne par les particuliers.
- Pas de tarifs, de bons de commande, de cautions ni de facturation.
- Pas de photos d'état des lieux.
- Pas de vente d'occasion.
- Pas de gestion des comptes : les comptes de démonstration sont créés au démarrage, sans création de compte, mot de passe oublié ni changement de mot de passe.
- Sécurité limitée à la connexion : prototype en local, sans HTTPS ; à renforcer avant toute mise en service.
- Question ouverte pour Brice : une agence peut-elle annuler une réservation saisie par une autre agence ? Ce matin, oui.
- La liste des grands comptes démarre avec BTP Rhone seul (seul nommé dans le dossier) : question à poser à Brice (quels sont les 40 grands comptes ? la commerciale grands comptes doit-elle pouvoir la modifier ?).
- L'historique ne montre que les réservations, pas les actions de l'atelier (mises en atelier, VGP) : question à poser à Brice.
- Pas d'exception à la règle des 48 h, même pour la Direction ou pour corriger une double réservation : question à poser à Brice.
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
2. Étant donné que MINI07 est en atelier du 01/10 au 20/10, quand Lyon Est cherche une « Mini-pelle 1.8 t » du 16/10 au 17/10, alors MINI07 apparaît indisponible (en atelier) et MINI12 (Saint-Etienne) est proposée.
3. Étant donné que la dernière VGP de NAC089 date du 05/03/2026, quand une agence cherche une « Nacelle 16 m » du 02/11 au 05/11, alors NAC089 apparaît indisponible avec le motif « VGP non à jour, contacter l'atelier ».
4. Étant donné que NAC140 (Grenoble) est libre du 26/10 au 28/10 et que sa VGP est à jour, quand Lyon Est la réserve pour Facades Martin, alors la réservation est acceptée et apparaît dans le planning, saisie par Lyon Est.
5. Étant donné que les réservations ont été reprises des Excel, quand on ouvre l'onglet Anomalies, alors on voit la double réservation NAC112 (BTP Rhone / Maconnerie Duclos) et NAC089 réservée pour Facades Martin avec une VGP échue.

Tests ajoutés après la recette de Brice :

6. Étant donné que NAC112 est indisponible sur la recherche du 16/10 au 17/10, quand Villeurbanne clique sur « Réserver à d'autres dates », choisit du 20/10 au 22/10 dans le formulaire et confirme pour Maconnerie Duclos, alors la réservation est acceptée sans avoir changé la recherche.
7. Étant donné que NAC112 est réservée du 14/10 au 18/10, quand Villeurbanne choisit du 17/10 au 19/10 dans le formulaire de réservation, alors la réservation est refusée avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 ».
8. Étant donné que NAC112 est réservée deux fois en même temps (BTP Rhone et Maconnerie Duclos) et que NAC089 est réservée pour Facades Martin avec une VGP échue, quand on ouvre le planning, alors ces trois réservations affichent « Attention : anomalie » sous leur machine (double réservation pour les deux NAC112, VGP non à jour pour NAC089) ; depuis la carte révélation 2, les réservations BTP Rhone sans bon de commande (NAC112, NAC140) affichent aussi « grand compte sans bon de commande », et les autres réservations n'affichent rien.
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
19. Étant donné qu'aujourd'hui est le 12/10/2026 et que la VGP de NAC089 était valable jusqu'au 05/09/2026, quand l'atelier ouvre l'onglet Atelier, alors NAC089 affiche « VGP en retard » avec en dessous « dernière VGP 05/03/2026, valable jusqu'au 05/09/2026 », et NAC112 affiche « VGP à jour » avec en dessous « dernière VGP 10/07/2026, valable jusqu'au 10/01/2027 ».
20. Étant donné que NAC089 est la seule nacelle « VGP en retard », quand l'atelier ouvre l'onglet Atelier, alors NAC089 est la première ligne ; et quand il filtre sur le type « Nacelle 16 m » ou tape « nac », NAC089 reste en premier.
21. Étant donné que la VGP de NAC089 est en retard, quand l'atelier saisit « VGP réalisée le 12/10/2026 » et clique sur « Mettre à jour la VGP », alors NAC089 affiche « VGP à jour » avec en dessous « dernière VGP 12/10/2026, valable jusqu'au 12/04/2027 ».
22. Étant donné que Brice est connecté avec le compte Direction, quand il ouvre l'outil, alors il voit les 5 onglets ; quand il réserve COMP30 du 20/10 au 21/10 pour BTP Rhone en choisissant l'agence « Annecy », alors la réservation est acceptée et saisie par Annecy (sans agence choisie, elle est refusée) ; quand il annule la réservation de Maconnerie Duclos, alors l'historique affiche « annulée le 12/10/2026 par Direction » ; et il peut prévoir un passage en atelier et mettre à jour une VGP.
23. Étant donné qu'aujourd'hui est le 12/10/2026, quand un utilisateur se connecte et arrive sur l'onglet « Rechercher », alors les 12 machines du parc s'affichent sans clic, avec « Du 12/10/2026 au 12/10/2026 » ; COMP21 (réservée par M. Pereira ce jour-là), ECH40 (Constructions Alpes), MINI07 (en atelier) et NAC089 (VGP en retard) sont indisponibles, les 8 autres disponibles.

Tests ajoutés avec les cartes révélation :

24. Étant donné que NAC140 (Grenoble) est réservée par BTP Rhone du 19/10 au 23/10, quand Villeurbanne la réserve du 24/10 au 25/10, alors c'est refusé avec « Transfert depuis Grenoble impossible : la machine doit être libre la veille du départ (23/10/2026) — Période déjà occupée par BTP Rhone du 19/10/2026 au 23/10/2026 » ; quand Grenoble la réserve sur les mêmes dates, c'est accepté (pas de transfert) ; quand Villeurbanne la réserve du 25/10 au 26/10, c'est accepté (la veille, le 24/10, est libre). De même, MINI07 (Lyon Est, en atelier jusqu'au 20/10) est refusée à Villeurbanne à partir du 21/10 mais acceptée pour Lyon Est.
25. Étant donné que NAC140 (Grenoble) est réservée par BTP Rhone du 19/10 au 23/10, quand Villeurbanne lance une recherche, alors NAC140 affiche « Réservée du 19/10/2026 au 23/10/2026 · BTP Rhone », « Libre du 12/10/2026 au 18/10/2026 » et « Libre à partir du 25/10/2026 » ; pour Grenoble, le second créneau est « Libre à partir du 24/10/2026 ». NAC118 (VGP valable jusqu'au 15/10) affiche « Libre du 12/10/2026 au 14/10/2026 » et « VGP échue à partir du 15/10/2026 ».
26. Étant donné que BTP Rhone est un grand compte, quand Lyon Est réserve COMP30 du 26/10 au 27/10 pour « btp rhone » sans bon de commande, alors c'est refusé avec « BTP Rhone est un grand compte : le numéro de bon de commande est obligatoire » ; avec le bon de commande « BC-2026-0412 », c'est accepté, enregistré au nom de « BTP Rhone », et le planning affiche « BC BC-2026-0412 » ; pour Facades Martin (pas grand compte), aucun bon de commande n'est demandé. Les deux réservations BTP Rhone reprises des Excel (NAC112, NAC140) apparaissent dans les anomalies comme « grand compte sans bon de commande ».
27. Étant donné que Facades Martin n'est pas un grand compte, quand Julie l'ajoute dans l'onglet « Grands comptes », alors la réservation NAC089 de Facades Martin apparaît dans les anomalies comme « grand compte sans bon de commande », et une nouvelle réservation pour Facades Martin sans bon de commande est refusée ; ajouter « facades martin » une deuxième fois est refusé (« Facades Martin est déjà un grand compte ») ; un compte agence voit la liste mais ne peut pas la modifier ; dans le formulaire de réservation, « Facades Martin (grand compte) » est proposé.
28. Étant donné les données de départ, quand on ouvre le planning, alors la frise affiche 2 semaines à partir du 12/10 avec les 12 machines ; NAC112 a deux barres rouges l'une sous l'autre (BTP Rhone du 14 au 18/10, Maconnerie Duclos du 16 au 17/10) ; MINI07 a une barre orange « atelier » jusqu'au 20/10 ; NAC118 est hachurée à partir du 15/10 ; « Semaine suivante » (en vue 1 semaine) affiche le 19 au 25/10 ; avec le filtre client « BTP Rhone », seules NAC112 et NAC140 restent.
29. Étant donné que Lyon Est est connecté, quand il clique sur la barre NAC112 de Maconnerie Duclos, alors le panneau affiche « NAC112 · Nacelle 12 m · Lyon Est », « Maconnerie Duclos », « du 16/10/2026 au 17/10/2026 », « saisie par Villeurbanne », l'anomalie de double réservation et le bouton « Annuler la réservation » ; après annulation, la barre disparaît et celle de BTP Rhone n'affiche plus que « grand compte sans bon de commande ». Le compte Commercial ne voit pas le bouton d'annulation.
30. Étant donné que NAC140 (Grenoble) est réservée par BTP Rhone du 19/10 au 23/10, quand Grenoble la réserve du 23/10 au 24/10, alors c'est refusé avec « Retour de BTP Rhone le 23/10/2026 : la machine est nettoyée et contrôlée ce jour-là, elle est relouable à partir du 24/10/2026 » ; du 24/10 au 25/10, c'est accepté ; et la frise de recherche de Grenoble affiche « Libre à partir du 24/10/2026 ».
31. Étant donné qu'aujourd'hui est le 12/10/2026, quand Lyon Est ouvre le détail de la réservation NAC112 de Maconnerie Duclos (début le 16/10), alors il voit « Annulable jusqu'au 14/10/2026 » et peut l'annuler ; quand il ouvre celle de MINI12 d'Artisan Ferreira (début le 13/10), alors il voit « Plus annulable (moins de 48 h avant le début) » et aucun bouton ; une demande d'annulation envoyée quand même est refusée avec « Annulation impossible : la location commence le 13/10/2026, il fallait annuler au plus tard le 11/10/2026. Le client doit garder la réservation. » ; NAC112 de BTP Rhone (début le 14/10) est encore annulable aujourd'hui.

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
