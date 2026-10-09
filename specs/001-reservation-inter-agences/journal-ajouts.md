# Journal des ajouts

Tout ce qui est dans le prototype sans être écrit dans la spec.

| # | Ajout | Pourquoi | Demandé par Brice ? |
|---|---|---|---|
| 1 | Option « Tous les types » dans la recherche | Permet de voir tout le parc sur une période | Non |
| 2 | Confirmation avant d'annuler une réservation | Éviter une annulation par erreur d'un clic | Non |
| 3 | Champ « Motif » quand on passe une machine en atelier | Reprend la colonne « remarque » des données (« verin casse ») | Non (vient des données) |

## Corrections de la spec

- 09/10 : test n°2, période passée du 13/10–15/10 au 15/10–16/10. MINI12 est réservée par Artisan Ferreira du 13 au 14/10 dans les données, le test tel qu'écrit ne pouvait pas passer.
- 09/10 : FR-002b, le bouton « Réserver » est grisé pour une machine indisponible (retour de recette du binôme). Remplace l'ancien ajout « bouton Réserver aussi sur les machines indisponibles ».
- 09/10 : US6 et FR-014 à FR-017, connexion obligatoire et trois profils (agence, atelier, commercial). Initiative du binôme : sans connexion, n'importe qui connaissant l'adresse modifie le planning. Non demandé par Brice : à lui présenter et à confirmer.
- 09/10, retour de Brice : pouvoir réserver une machine indisponible à d'autres dates directement depuis le bouton, sans changer la recherche. FR-002b remplacée (bouton « Réserver à d'autres dates » au lieu du bouton grisé), FR-002c / R9 ajoutée, tests 6 et 7.
