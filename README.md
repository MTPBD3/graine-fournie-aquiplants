# 🌱 Graine Fournie AQUIPLANTS

[![CI](https://github.com/MTPBD3/graine-fournie-aquiplants/actions/workflows/ci.yml/badge.svg)](https://github.com/MTPBD3/graine-fournie-aquiplants/actions/workflows/ci.yml)

Application web de gestion des arrivées et du stockage de graines fournies par les clients de la pépinière AQUIPLANTS (Eyragues, France).

## Contexte

AQUIPLANTS gère manuellement l'arrivée et le stockage de graines fournies par ses clients agriculteurs. Ce projet vise à remplacer ce système papier par une application web moderne.

## Stack technique

- **Backend** : Symfony 7 (API REST) + PHP 8.2
- **Frontend** : React 18 + Vite
- **Base de données** : MySQL 8
- **Auth** : JWT (LexikJWTAuthenticationBundle)
- **Infra** : Docker + Docker Compose
- **CI/CD** : GitHub Actions

## Jalons

| Jalon | Mois | Contenu | Statut |
|-------|------|---------|--------|
| J1 | Janvier 2026 | Cahier des charges fonctionnel | ✅ Livré |
| J2 | Février 2026 | Méthodologie & Conception UI/UX | ✅ Livré |
| J3 | Mars 2026 | Modélisation base de données | ✅ Livré |
| J4 | Avril 2026 | Architecture & Diagrammes UML | ✅ Livré |
| J5 | Mai 2026 | Développement, Sécurité & Tests | 🔄 En cours |
| J6 | Juin 2026 | Déploiement & Livrable final | ⏳ À venir |


## Maquettes

Wireframes et maquettes haute fidélité disponibles sur Figma :
[Voir les maquettes](https://www.figma.com/design/ywbMZtE731MNfb0sqrdyYH/Figma-Graine-Fournie-AQUIPLANTS?node-id=54-8033&t=UNuvO2izCszRlxVN-1)

## Prérequis

- Docker Desktop installé et démarré
- Git

## Lancement

```bash
git clone https://github.com/MTPBD3/graine-fournie-aquiplants.git
cd graine-fournie-aquiplants

# Copier et renseigner les variables d'environnement Docker
cp .env.docker.example .env.docker
# Éditer .env.docker avec les vraies valeurs si nécessaire

# Démarrer l'application (mode production)
docker compose --env-file .env.docker up -d --build
```

### Mode développement (avec Xdebug)

```bash
docker compose --env-file .env.docker -f docker-compose.yml -f docker-compose.dev.yml up -d --build
```

Le mode dev active `APP_ENV=dev`, `APP_DEBUG=1` et Xdebug dans le conteneur Symfony.

## URLs d'accès

| Service           | URL                            | Accessible depuis        |
|-------------------|--------------------------------|--------------------------|
| Application React | http://localhost:3000          | Réseau                   |
| API Symfony       | http://localhost:8000          | Réseau                   |
| phpMyAdmin        | http://127.0.0.1:8080          | Localhost uniquement      |

## Charger les données de test

```bash
docker exec gf_symfony php bin/console doctrine:fixtures:load --append
```

## Comptes de test

| Rôle           | Email                   | Mot de passe |
|----------------|-------------------------|--------------|
| Administrateur | testadmin@aquiplants.fr | admin        |
| Employé        | testuser@aquiplants.fr  | user         |

## Auteur

Projet individuel — Formation CDA (Concepteur Développeur d'Applications), Bachelor 3 DevOps.
