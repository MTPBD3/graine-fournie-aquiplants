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
| J4 | Avril 2026 | Architecture & Diagrammes UML | 🔄 En cours |
| J5 | Mai 2026 | Développement, Sécurité & Tests | ⏳ À venir |
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
docker compose up -d
```

## URLs d'accès

| Service           | URL                   |
|-------------------|-----------------------|
| Application React | http://localhost:3000 |
| API Symfony       | http://localhost:8000 |
| phpMyAdmin        | http://localhost:8080 |

## Charger les données de test

```bash
docker exec gf_symfony php bin/console doctrine:fixtures:load --append
```

## Comptes de test

| Rôle           | Email                   | Mot de passe |
|----------------|-------------------------|--------------|
| Administrateur | testadmin@aquiplants.fr | admin        |
| Employé        | testuser@aquiplants.fr  | user         |

## Résolution de problèmes

### Erreur de droits sur `var/` (cache / logs Symfony)

Après un `git clone` suivi de `docker compose up`, si le conteneur `gf_symfony` refuse de démarrer ou retourne une erreur du type `Unable to write to the cache directory` :

```bash
docker exec gf_symfony chown -R www-data:www-data var/
docker exec gf_symfony chmod -R ug+rwX var/
```

> **Pourquoi ça arrive ?** Le répertoire `var/` est exclu de Git (`.gitignore`). Docker crée alors le volume anonyme en `root:root`. Le script `init.sh` corrige les droits automatiquement au démarrage, mais si le premier démarrage est interrompu ou si le volume Docker persiste d'une session précédente, les permissions peuvent rester incorrectes.

### Relancer depuis zéro

```bash
docker compose down -v    # supprime volumes MySQL, vendor et var
docker compose up -d --build
```

---

## Auteur

Projet individuel — Formation CDA (Concepteur Développeur d'Applications), Bachelor 3 DevOps.
