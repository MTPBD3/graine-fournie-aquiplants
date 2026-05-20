# CLAUDE.md — Backend Symfony

API REST stateless — PHP 8.2 / Symfony 7.4 / Doctrine ORM / MySQL 8.0 / JWT.
Voir le CLAUDE.md racine pour l'architecture globale et Docker.

## Commandes courantes (dans le conteneur)

```bash
# Toujours préfixer avec : docker exec -it gf_symfony

php bin/console doctrine:migrations:migrate
php bin/console doctrine:migrations:diff       # après changement d'entité
php bin/console make:migration                 # générer migration
php bin/console doctrine:schema:validate
php bin/console debug:router
php bin/console cache:clear
php bin/console app:import-csv --no-debug      # import CSV espèces/plants/UV
```

## Architecture

**MVC + REST stateless**

- `src/Controller/` — handlers HTTP avec attribut `#[Route]`. Chaque controller gère une ressource.
- `src/Entity/` — 11 entités Doctrine (mapping par attributs PHP, pas de XML/YAML).
- `src/Repository/` — un repository par entité, requêtes personnalisées ici.
- `src/Command/` — commandes console (`app:import-csv`).
- `src/Service/` — services partagés (`LogService`).
- `config/packages/security.yaml` — 3 firewalls JWT : `dev`, `login` (public), `api`.

## Entités

| Entité              | Table                  | Particularités                                              |
|---------------------|------------------------|-------------------------------------------------------------|
| `Utilisateur`       | `utilisateur`          | Implements `UserInterface`, auth email/password             |
| `Client`            | `client`               | Client de la pépinière                                      |
| `Espece`            | `espece`               | 3 218 lignes CSV. **Plusieurs espèces peuvent partager le même nom.** |
| `Plant`             | `plant`                | FK `id_espece` nullable. Chaque plant a son propre id_espece. |
| `Uv`                | `uv`                   | FK `id_espece`. Lookup par nom d'espèce dans EspeceController. |
| `GfClient`          | `gf_client`            | Entité centrale, liée à Client + Plant + Emplacement        |
| `Emplacement`       | `emplacement`          | Code `[A-D]-[1-4]`. Auto-supprimé si plus de sachets.       |
| `CommandeASemer`    | `commande_a_semer`     |                                                             |
| `GfHistoClient`     | `gf_histo_client`      | Historique semis                                            |
| `HistoGfDeposee`    | `histo_gf_deposee`     | Statuts: `en_attente`, `en_stock`, `epuise`                 |
| `Log`               | `log`                  | Audit trail, FK `Utilisateur`                               |

## Sécurité

- Clés JWT dans `config/jwt/private.pem` / `config/jwt/public.pem` (gitignorées).
- Passphrase : variable d'env `JWT_PASSPHRASE`.
- Hiérarchie : `ROLE_ADMIN > ROLE_EMPLOYE`.
- `/api/login` public. Tout le reste `ROLE_EMPLOYE` minimum.

## Ajouter une feature

1. **Nouvelle entité** : `php bin/console make:entity` → `make:migration` → `migrate`.
2. **Nouveau controller** : attribut `#[Route('/api/...')]`, dépendances autowirées.
3. **Toujours committer la migration** avec les changements d'entité.

## Variables d'environnement clés

| Variable        | Dev (Docker)                                              |
|-----------------|-----------------------------------------------------------|
| `DATABASE_URL`  | `mysql://aquiplants:aquiplants@mysql_db:3306/aquiplants_db` |
| `JWT_PASSPHRASE`| `aquiplants`                                              |
| `CORS_ALLOW_ORIGIN` | `^https?://(localhost\|127\.0\.0\.1)(:[0-9]+)?$`      |
