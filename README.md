# Vallet Location — Réservation inter-agences

Prototype : chercher une machine dans les 7 agences et la réserver sans erreur.

- Spec une page (format des consignes) : `SPEC.md`
- Spec Kit (spec détaillée, plan, tâches, journal des ajouts) : `specs/001-reservation-inter-agences/`
- Back Laravel (API + règles) : `back/`
- Front Nuxt : `web/`

## Lancer

Prérequis : Docker Desktop. Rien d'autre à installer.

1. Installer les dépendances PHP (une seule fois) :

   ```bash
   docker run --rm -v "$PWD/back:/app" -w /app composer:2 install --ignore-platform-reqs
   ```

2. Construire et démarrer :

   ```bash
   docker compose up -d --build
   ```

3. Ouvrir http://localhost:3000

Les images contiennent une version compilée du code : après une modification du code, relancer `docker compose up -d --build`.

La base est remise à zéro avec les données des consignes à chaque démarrage du back (`docker compose restart back`), ce qui déconnecte tout le monde. Date du jour simulée : 12/10/2026.

## Comptes de démonstration

Mot de passe commun : `vallet-demo-2026` (comptes fictifs, recréés à chaque démarrage).

| Profil | E-mail | Peut |
|---|---|---|
| Agent d'agence | `lyon-est@vallet.test`, `villeurbanne@vallet.test`, `grenoble@vallet.test`, `saint-etienne@vallet.test`, `clermont-ferrand@vallet.test`, `annecy@vallet.test`, `valence@vallet.test` | rechercher, réserver, modifier les réservations saisies par son agence ; jamais annuler |
| Responsable d'agence | `responsable.lyon-est@vallet.test` (Sandrine Morin), et `responsable.<agence>@vallet.test` pour les 6 autres agences | comme un agent, et modifier ou annuler les réservations saisies par son agence ou portant sur une machine de son agence |
| Atelier | `atelier@vallet.test` | rechercher, gérer les passages en atelier, mettre à jour la VGP |
| Commercial | `julie.ferrand@vallet.test` | rechercher, consulter, tenir la liste des grands comptes |
| Direction | `brice.vallet@vallet.test` | tout : réserver (en choisissant l'agence), annuler, atelier, VGP, grands comptes, consulter |

## Tests

Dans un conteneur séparé, pour ne pas toucher à la base de l'application qui tourne :

```bash
docker compose run --rm back sh -c "cp .env.example .env && php artisan key:generate --force && php artisan test"
```
