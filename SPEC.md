# Vallet Location — Spec du prototype « Réservation inter-agences »

Date du jour dans le prototype : lundi 12 octobre 2026. Détail Spec Kit : `specs/001-reservation-inter-agences/spec.md`.

## Le problème
Chaque agence tient son propre Excel de planning et personne ne connaît la disponibilité réelle du parc : une dizaine de doubles réservations par mois rien qu'à Lyon Est (DOC 2), et des nacelles réservées avec une VGP échue (DOC 3, DOC 5).

## Les utilisateurs
- **Agences** (Sandrine Morin, Lyon Est) : cherchent une machine dans les 7 agences et la réservent pour un client.
- **Atelier** (Mehdi Arfaoui et ses techniciens) : déclarent et prévoient les passages en atelier, et mettent à jour les VGP.
- **Commerciale grands comptes** (Julie Ferrand) : consulte en temps réel ce qui est disponible.

## Ce que l'outil permet
1. En tant qu'agence, je veux chercher une machine par type et par période dans les 7 agences, afin de ne plus appeler les agences une par une (jusqu'à 30 min aujourd'hui, DOC 2).
2. En tant qu'agence, je veux réserver une machine pour un client, sur la période cherchée ou à d'autres dates choisies depuis le bouton « Réserver », afin que toutes les agences voient la réservation immédiatement.
3. En tant qu'agence, je veux annuler une réservation, afin de libérer la machine et corriger une double réservation.
4. En tant qu'atelier, je veux déclarer ou prévoir un passage en atelier avec une date de début et de fin, et mettre à jour la VGP, afin qu'on ne promette plus une machine en panne, en entretien ou non conforme (DOC 5).
5. En tant qu'utilisateur, je veux voir les réservations qui violent une règle, afin de les traiter.
6. En tant qu'utilisateur, je dois me connecter, afin que personne d'extérieur ne puisse modifier le planning ou une VGP.

## Les règles que l'outil doit faire respecter
- R1. Une machine ne peut pas avoir deux réservations qui se chevauchent, dates incluses (DOC 2, DOC 3).
- R2. Une machine n'est pas réservable sur une période qui chevauche un de ses passages en atelier, dates incluses (DOC 5 : MINI07 à l'atelier depuis le jeudi 01/10 jusqu'au 20/10).
- R3. Une nacelle n'est réservable que si sa dernière VGP réalisée, valable 6 mois, couvre toute la période ; sans VGP, elle n'est pas réservable (DOC 5 : « une nacelle sans VGP à jour ne doit pas sortir »).
- R4. La date de fin est égale ou postérieure à la date de début.
- R5. On ne réserve pas dans le passé (avant le 12/10/2026).
- R6. Une réservation indique le client, la machine, les dates et l'agence qui l'a saisie ; l'agence est celle du compte connecté.
- R7. Une machine indisponible affiche son motif (période occupée et par qui, « en atelier du … au … », « VGP non à jour, contacter l'atelier ») ; son bouton devient « Réserver à d'autres dates » (retour de Brice).
- R8. Seules les agences réservent et annulent ; seul l'atelier gère les passages en atelier et met à jour les VGP ; la commerciale consulte.
- R9. Dans le formulaire de réservation, les dates sont pré-remplies avec la période cherchée et modifiables sans relancer la recherche ; les règles R1 à R5 sont revérifiées à l'envoi et un refus affiche son motif (retour de Brice).
- R10. Dans le planning, chaque réservation concernée par une anomalie (double réservation, VGP non à jour, machine en atelier) affiche sous sa machine un avertissement « Attention : anomalie » avec son type (retour de Brice).
- R11. Le planning se filtre par client, choisi dans la liste des clients qui ont une réservation, pour voir toutes les machines réservées par une entreprise ; « Tous les clients » réaffiche tout (retour de Brice).
- R12. Le planning se filtre aussi par machine, par agence de la machine, par agence de saisie et par période (réservations qui chevauchent la période) ; les filtres se cumulent, un bouton « Réinitialiser les filtres » les vide tous, et le message « Aucune réservation ne correspond aux filtres » s'affiche si rien ne correspond (initiative du binôme, non demandée par Brice).
- R13. Pendant un chargement (onglet, recherche, réservation), l'écran affiche un indicateur « Chargement… » ; tant que les données ne sont pas chargées, il n'affiche jamais « Aucune anomalie », « Aucune réservation » ni une liste vide. Si le chargement échoue, il affiche « Impossible de charger les données » avec un bouton « Réessayer », jamais un résultat vide (retour de Brice).
- R14. Un onglet « Historique » liste toutes les réservations, y compris annulées, avec leur statut : « à venir » (début après aujourd'hui), « en cours » (aujourd'hui dans la période), « terminée » (fin avant aujourd'hui), « annulée » (avec la date et l'agence qui a annulé). Il a les mêmes filtres que le planning, plus un filtre par statut (retour de Brice).
- R15. Annuler une réservation ne l'efface plus : elle est marquée annulée, disparaît du planning et des anomalies, et libère la machine ; une réservation déjà annulée ne peut pas l'être une seconde fois (retour de Brice, nécessaire à R14).
- R16. Un passage en atelier a une date de début, une date de fin (égale ou postérieure au début) et un motif facultatif (ex. « VGP », « vérin cassé ») ; le début est aujourd'hui ou plus tard, sauf pour les données reprises ; deux passages d'une même machine ne peuvent pas se chevaucher (retour de Brice).
- R17. L'atelier peut prévoir un passage dans le futur, par exemple une VGP ; une VGP prévue ne compte pas comme réalisée : R3 continue de s'appliquer tant que l'atelier n'a pas mis à jour la VGP avec la date de la VGP réalisée (retour de Brice ; prudence légale, DOC 5).
- R18. Un passage en cours se termine avec « Remettre en service » (la machine redevient disponible dès aujourd'hui) ; un passage prévu s'annule avec « Annuler ce passage » (retour de Brice).
- R19. Un passage en atelier peut chevaucher une réservation existante (une panne ne se refuse pas) : l'atelier voit alors la liste des réservations concernées, et chacune apparaît dans les anomalies comme « réservée pendant un passage en atelier » (conséquence de R16).
- R20. L'onglet Atelier se filtre par nom de machine (saisie libre, une partie du nom suffit, majuscules ou minuscules indifférentes) et par type de machine (liste) ; les deux filtres se cumulent et le message « Aucune machine ne correspond » s'affiche si rien ne correspond (retour de Brice : retrouver une machine parmi les 400 du parc).
- R21. Dans l'onglet Atelier, l'état VGP d'une nacelle s'écrit en toutes lettres, pas seulement en couleur : « VGP en retard » (en rouge) si elle n'est plus valable aujourd'hui ou s'il n'y en a aucune, sinon « VGP à jour » (en vert), suivi de la date de la dernière VGP réalisée et de sa date de fin de validité (retour de Brice).
- R22. Dans l'onglet Atelier, les machines « VGP en retard » apparaissent en premier, puis les autres dans l'ordre habituel (type puis nom) ; l'ordre est conservé quand on filtre (retour de Brice).
- R23. Dans l'onglet Atelier, l'action sur la VGP s'appelle « Mettre à jour la VGP » : l'atelier indique la date de la VGP réalisée (aujourd'hui ou avant) et valide avec ce bouton (retour de Brice).

## Ce que l'outil ne fait pas ce matin
- Pas de réservation en ligne par les particuliers (étape 2, une fois la disponibilité fiable).
- Pas de tarifs, bons de commande, cautions ni facturation.
- Pas de photos d'état des lieux, pas de vente d'occasion, pas de transfert de machine entre agences.
- Pas de gestion des comptes (comptes de démonstration fournis) ni de HTTPS : prototype local.
- L'historique ne montre que les réservations, pas les actions de l'atelier : question à poser à Brice.
- Une VGP prévue ne débloque pas la nacelle à l'avance : question à poser à Brice.

## Nos 5 tests
1. Étant donné que NAC112 est réservée du 14/10 au 18/10 pour BTP Rhone, quand Villeurbanne cherche une « Nacelle 12 m » du 16/10 au 17/10, alors NAC112 apparaît indisponible avec le motif « Période déjà occupée par BTP Rhone du 14/10/2026 au 18/10/2026 » et son bouton affiche « Réserver à d'autres dates ».
2. Étant donné que MINI07 est en atelier du 01/10 au 20/10, quand une agence cherche une « Mini-pelle 1.8 t » du 15/10 au 16/10, alors MINI07 apparaît indisponible (en atelier) et MINI12 (Saint-Etienne) est proposée.
3. Étant donné que la dernière VGP de NAC089 date du 05/03/2026, quand une agence cherche une « Nacelle 16 m » du 02/11 au 05/11, alors NAC089 apparaît indisponible avec le motif « VGP non à jour, contacter l'atelier ».
4. Étant donné que NAC140 (Grenoble) est libre du 26/10 au 28/10 et que sa VGP est à jour, quand Lyon Est la réserve pour Facades Martin, alors la réservation est acceptée et apparaît dans le planning, saisie par Lyon Est.
5. Étant donné que les réservations ont été reprises des Excel, quand on ouvre l'onglet Anomalies, alors on voit la double réservation NAC112 (BTP Rhone / Maconnerie Duclos) et NAC089 réservée pour Facades Martin avec une VGP échue.

## Tests ajoutés après la recette de Brice
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
20. Étant donné que NAC089 est la seule nacelle « VGP en retard », quand l'atelier ouvre l'onglet Atelier, alors NAC089 est la première ligne ; et quand il filtre sur le type « Nacelle 16 m » ou tape « nac », NAC089 reste en premier.
21. Étant donné que la VGP de NAC089 est en retard, quand l'atelier saisit « VGP réalisée le 12/10/2026 » et clique sur « Mettre à jour la VGP », alors NAC089 affiche « VGP à jour · dernière VGP 12/10/2026, valable jusqu'au 12/04/2027 ».
