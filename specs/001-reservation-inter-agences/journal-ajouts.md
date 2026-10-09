# Journal des ajouts

Tout ce qui est dans le prototype sans être écrit dans la spec.

| # | Ajout | Pourquoi | Demandé par Brice ? |
|---|---|---|---|
| 1 | Option « Tous les types » dans la recherche | Permet de voir tout le parc sur une période | Non |
| 2 | Confirmation avant d'annuler une réservation | Éviter une annulation par erreur d'un clic | Non |
| 3 | Champ « Motif » quand on passe une machine en atelier | Reprend la colonne « remarque » des données (« verin casse ») | Non (vient des données) |
| 4 | Filtres du planning par machine, agence de la machine, agence de saisie et période, avec « Réinitialiser les filtres » (R12) | Initiative du binôme pour retrouver vite une réservation, dans le prolongement du filtre client demandé par Brice | Non |
| 5 | Recherche lancée automatiquement à l'arrivée sur l'onglet « Rechercher », tout le parc affiché pour aujourd'hui (R25) | Initiative du binôme : voir l'état du parc sans clic | Non |
| 6 | Dans la recherche, ce qui occupe chaque machine et ses créneaux libres sur 60 jours (R27) | Initiative du binôme : éviter de relancer la recherche date après date pour trouver un créneau | Non |

## Cartes révélation

- Carte 1 : « Une machine qui vient d'une autre agence voyage une demi-journée : elle doit être libre la veille du départ. » FR-034 / R26 ajoutée, test 24. Test 2 décalé du 15-16/10 au 16-17/10 : avec cette règle, MINI12 (Saint-Etienne) n'est plus proposée à Lyon Est le 15/10 car elle est réservée la veille (14/10).
- Carte 2 : « Une réservation pour un grand compte n'est valable qu'avec un numéro de bon de commande. » FR-036 / R28 ajoutée, test 26. Liste des grands comptes limitée à BTP Rhone (seul nommé, DOC 6) : question ouverte pour Brice. Les réservations BTP Rhone reprises des Excel n'ont pas de bon de commande, elles deviennent des anomalies ; test 8, R10, FR-011 et FR-018 mis à jour en conséquence.

## Style

- 09/10, demande du binôme : style revu sans changer aucune règle. Filtres présentés en barre homogène (Rechercher, Planning, Historique, Atelier), motifs d'indisponibilité alignés à gauche sans puces, bouton « Réserver à d'autres dates » sur une ligne, états VGP et passages en atelier présentés en blocs lisibles. Tests 19 et 21 reformulés : l'état VGP et sa date sont sur deux lignes au lieu d'être séparés par « · ».

## Corrections de la spec

- 09/10 : test n°2, période passée du 13/10–15/10 au 15/10–16/10. MINI12 est réservée par Artisan Ferreira du 13 au 14/10 dans les données, le test tel qu'écrit ne pouvait pas passer.
- 09/10 : FR-002b, le bouton « Réserver » est grisé pour une machine indisponible (retour de recette du binôme). Remplace l'ancien ajout « bouton Réserver aussi sur les machines indisponibles ».
- 09/10 : US6 et FR-014 à FR-017, connexion obligatoire et trois profils (agence, atelier, commercial). Initiative du binôme : sans connexion, n'importe qui connaissant l'adresse modifie le planning. Non demandé par Brice : à lui présenter et à confirmer.
- 09/10, retour de Brice : pouvoir réserver une machine indisponible à d'autres dates directement depuis le bouton, sans changer la recherche. FR-002b remplacée (bouton « Réserver à d'autres dates » au lieu du bouton grisé), FR-002c / R9 ajoutée, tests 6 et 7.
- 09/10, retour de Brice : signaler visuellement dans le planning les réservations qui ont une anomalie. FR-018 / R10 ajoutée, test 8.
- 09/10, retour de Brice : filtrer le planning pour voir toutes les machines réservées par une entreprise. FR-019 / R11 ajoutée (filtre par client), test 9.
- 09/10, retour de Brice : indicateur de chargement, et surtout ne jamais afficher « Aucune anomalie » pendant le chargement (fausse impression que tout va bien). FR-021 / R13 ajoutée, tests 11 et 12. Le cas « chargement en échec » est inclus car il produit le même faux « tout va bien ».
- 09/10, retour de Brice : historique de ce qui a été réservé (passé, en cours, à venir) avec les mêmes filtres. FR-022 / R14 ajoutée, tests 13 et 14. FR-023 / R15 ajoutée : l'annulation marque la réservation au lieu de l'effacer, sinon les annulations ne pourraient pas apparaître dans l'historique. Les actions de l'atelier ne sont pas incluses (question à poser à Brice).
- 09/10, retours de Brice : date de début et de fin pour un passage en atelier, et possibilité de prévoir un passage futur (ex. VGP). FR-004 / R2 réécrite (chevauchement avec un passage), FR-024 à FR-026 / R16 à R18 ajoutées, tests 15 et 16. Conséquence ajoutée : FR-027 / R19, un passage peut chevaucher une réservation (une panne ne se refuse pas) et la réservation devient une anomalie, test 17. Choix prudent noté comme question pour Brice : une VGP prévue ne débloque pas la nacelle à l'avance. MINI07 : début du passage fixé au 01/10 d'après DOC 5 (« depuis jeudi »).
- 09/10, retour de Brice : retrouver une machine dans l'onglet Atelier quand le parc comptera 400 machines. FR-028 / R20 ajoutée (filtre par nom et par type), test 18.
- 09/10, retour de Brice : écrire « en retard » plutôt que de compter sur la date en rouge. Interprété comme l'état VGP de l'onglet Atelier (seule date affichée en rouge). FR-029 / R21 ajoutée, test 19.
- 09/10, retour de Brice : faire remonter en premier les machines en retard de VGP dans l'onglet Atelier. FR-030 / R22 ajoutée, test 20.
- 09/10, retour de Brice : renommer « Enregistrer la VGP » en « Mettre à jour la VGP ». Libellé changé partout (spec, bouton, message d'erreur), FR-031 / R23 ajoutée, test 21.
- 09/10, demande de Brice : un compte pour le DG qui voit tout et peut tout faire. Profil « Direction » ajouté, R6, R8, FR-010 et FR-015 adaptées, FR-032 / R24 ajoutée, test 22. Choix du binôme à valider : le DG n'appartenant à aucune agence, il choisit l'agence de saisie à chaque réservation, et ses annulations sont signées « Direction ».
