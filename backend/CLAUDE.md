# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

PHP 8.2 / Symfony 7.4 REST API backend for "Graine Fournie Aquiplants" — a seed bag management system for an agricultural nursery. Uses Doctrine ORM with MySQL (dev) and JWT authentication.

## Common Commands

```bash
# Start Docker services (PostgreSQL container)
docker compose up -d

# Install dependencies
composer install

# Database setup
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
php bin/console doctrine:fixtures:load

# Full database reset
php bin/console doctrine:database:drop --force && php bin/console doctrine:database:create && php bin/console doctrine:migrations:migrate && php bin/console doctrine:fixtures:load

# Generate migration after entity changes
php bin/console make:migration

# Start dev server
symfony serve
# or
php -S localhost:8000 -t public

# Validate DB schema against entities
php bin/console doctrine:schema:validate

# Debug routes
php bin/console debug:router

# Clear cache
php bin/console cache:clear
```

## Architecture

**MVC + Stateless REST API**

- `src/Controller/` — HTTP handlers using `#[Route]` attributes. Currently `AuthController` (login, `/api/me`) and `GfClientController` (seed bag CRUD).
- `src/Entity/` — 10 Doctrine ORM entities (see below). Attributes-based mapping (no XML/YAML).
- `src/Repository/` — One repository per entity; custom queries go here.
- `config/packages/security.yaml` — JWT firewalls. Three firewalls: `dev`, `login` (public, issues tokens), `api` (validates JWT). Role hierarchy: `ROLE_ADMIN` > `ROLE_EMPLOYE`.

**Domain Model (10 entities):**

| Entity | Purpose |
|--------|---------|
| `Utilisateur` | App user (implements `UserInterface`), auth via email |
| `Client` | Agricultural client |
| `Plant` | Plant species |
| `GfClient` | Seed bag inventory record (core entity) |
| `CommandeASemer` | Sowing order |
| `Emplacement` | Storage shelf/location |
| `GfHistoClient` | Sowing history per seed bag |
| `HistoGfDeposee` | Deposit history per seed bag |
| `Uv` | Seedling unit (Unité de Végétal) |
| `Log` | Audit log linked to `Utilisateur` |

## Security

- JWT keys live in `config/jwt/private.pem` and `config/jwt/public.pem` (gitignored, passphrase: see `.env`).
- All `/api/*` routes require `ROLE_EMPLOYE` minimum; `/api/admin/*` requires `ROLE_ADMIN`.
- `/api/login` is public.

## Environment

The `.env` file contains dev defaults. Override locally with `.env.local` (not committed).

Key variables:
- `DATABASE_URL` — MySQL by default (`graine_fournie_aquiplants` db). Docker Compose provides PostgreSQL on port 5432 as an alternative.
- `JWT_PASSPHRASE` — passphrase for JWT RSA keys.
- `CORS_ALLOW_ORIGIN` — regex for allowed origins (defaults to localhost).

## Adding New Features

- New entity: `php bin/console make:entity` → generates entity + repository → run `php bin/console make:migration`.
- New controller: use `#[Route('/api/...')]` attribute, autowire dependencies via constructor.
- Migrations must be committed alongside entity changes.
