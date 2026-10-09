# Vallet Location — Spec du prototype « Réservation inter-agences »

Date du jour dans le prototype : lundi 12 octobre 2026. Détail Spec Kit : `specs/001-reservation-inter-agences/spec.md`.

## Le problème
Chaque agence tient son propre Excel de planning et personne ne connaît la disponibilité réelle du parc : une dizaine de doubles réservations par mois rien qu'à Lyon Est (DOC 2), et des nacelles réservées avec une VGP échue (DOC 3, DOC 5).

## Les utilisateurs
- **Agences** (Sandrine Morin, Lyon Est) : cherchent une machine dans les 7 agences et la réservent pour un client.
- **Atelier** (Mehdi Arfaoui et ses techniciens) : déclarent une machine en atelier et enregistrent les VGP.
- **Commerciale grands comptes** (Julie Ferrand) : consulte en temps réel ce qui est disponible.

## Ce que l'outil permet
1. En tant qu'agence, je veux chercher une machine par type et par période dans les 7 agences, afin de ne plus appeler les agences une par une (jusqu'à 30 min aujourd'hui, DOC 2).
2. En tant qu'agence, je veux réserver une machine disponible pour un client, afin que toutes les agences voient la réservation immédiatement.
3. En tant qu'agence, je veux annuler une réservation, afin de libérer la machine et corriger une double réservation.
4. En tant qu'atelier, je veux passer une machine en atelier jusqu'à une date et enregistrer une VGP, afin qu'on ne promette plus une machine en panne ou non conforme (DOC 5).
5. En tant qu'utilisateur, je veux voir les réservations reprises des Excel qui violent une règle, afin de les traiter.
6. En tant qu'utilisateur, je dois me connecter, afin que personne d'extérieur ne puisse modifier le planning ou une VGP.

## Les règles que l'outil doit faire respecter
- R1. Une machine ne peut pas avoir deux réservations qui se chevauchent, dates incluses (DOC 2, DOC 3).
- R2. Une machine en atelier n'est pas réservable sur une période qui commence au plus tard à sa date de retour (DOC 5 : MINI07 jusqu'au 20/10).
- R3. Une nacelle n'est réservable que si sa VGP, valable 6 mois, couvre toute la période ; sans VGP, elle n'est pas réservable (DOC 5 : « une nacelle sans VGP à jour ne doit pas sortir »).
- R4. La date de fin est égale ou postérieure à la date de début.
- R5. On ne réserve pas dans le passé (avant le 12/10/2026).
- R6. Une réservation indique le client, la machine, les dates et l'agence qui l'a saisie ; l'agence est celle du compte connecté.
- R7. Une machine indisponible affiche son motif (période occupée et par qui, en atelier jusqu'au…, « VGP non à jour, contacter l'atelier ») et son bouton « Réserver » est grisé.
- R8. Seules les agences réservent et annulent ; seul l'atelier passe une machine en atelier et enregistre une VGP ; la commerciale consulte.

## Ce que l'outil ne fait pas ce matin
- Pas de réservation en ligne par les particuliers (étape 2, une fois la disponibilité fiable).
- Pas de tarifs, bons de commande, cautions ni facturation.
- Pas de photos d'état des lieux, pas de vente d'occasion, pas de transfert de machine entre agences.
- Pas de gestion des comptes (comptes de démonstration fournis) ni de HTTPS : prototype local.

## Nos 5 tests
1. Étant donné que NAC112 est réservée du 14/10 au 18/10 pour BTP Rhone, quand Villeurbanne cherche une « Nacelle 12 m » du 16/10 au 17/10, alors NAC112 apparaît indisponible avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 » et son bouton « Réserver » est grisé.
2. Étant donné que MINI07 est en atelier jusqu'au 20/10, quand une agence cherche une « Mini-pelle 1.8 t » du 15/10 au 16/10, alors MINI07 apparaît indisponible (en atelier) et MINI12 (Saint-Etienne) est proposée.
3. Étant donné que la dernière VGP de NAC089 date du 05/03/2026, quand une agence cherche une « Nacelle 16 m » du 02/11 au 05/11, alors NAC089 apparaît indisponible avec le motif « VGP non à jour, contacter l'atelier ».
4. Étant donné que NAC140 (Grenoble) est libre du 26/10 au 28/10 et que sa VGP est à jour, quand Lyon Est la réserve pour Facades Martin, alors la réservation est acceptée et apparaît dans le planning, saisie par Lyon Est.
5. Étant donné que les réservations ont été reprises des Excel, quand on ouvre l'onglet Anomalies, alors on voit la double réservation NAC112 (BTP Rhone / Maconnerie Duclos) et NAC089 réservée pour Facades Martin avec une VGP échue.
