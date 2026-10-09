# Vallet Location — Réservation inter-agences

Prototype : chercher une machine dans les 7 agences et la réserver sans erreur.

- Spec, plan, tâches, journal des ajouts : `specs/001-reservation-inter-agences/`
- Back Laravel (API + règles) : `back/`
- Front Nuxt : `web/`

## Lancer

Prérequis : Docker Desktop. Rien d'autre à installer.

1. Installer les dépendances PHP (une seule fois) :

   ```bash
   docker run --rm -v "$PWD/back:/app" -w /app composer:2 install --ignore-platform-reqs
   ```

2. Démarrer :

   ```bash
   docker compose up -d
   ```

3. Ouvrir http://localhost:3000 (le premier démarrage du front prend quelques minutes).

La base est remise à zéro avec les données des consignes à chaque démarrage du back. Date du jour simulée : 12/10/2026.

## Tests

```bash
docker compose exec back php artisan test
```
