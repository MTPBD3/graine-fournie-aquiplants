# Journal de bord — Graine Fournie AQUIPLANTS

## Semaine du 5 janvier 2026
- Début du projet fil rouge
- Analyse du PDF donné
- Prise en main du sujet : analyse du contexte AQUIPLANTS

## Semaine du 19 janvier 2026
- Rédaction du cahier des charges fonctionnel (contexte, problématiques, objectifs)
- Rédaction de la partie exigences techniques (stack Symfony + React + MySQL + Docker)
- Justification des choix techniques
- Livraison du Jalon 1

## Semaine du 2 février 2026
- Début du Jalon 2
- Rédaction de la partie méthodologie : choix de la méthode Scrum adaptée solo
- Mise en place du planning macro sur 6 mois
- Réflexion sur l'organisation Git (GitFlow)
- Création des wireframes sur Figma (desktop + mobile)

## Semaine du 16 février 2026
- Définition de la charte graphique (palette de couleurs, typographie)
- Réalisation des maquettes haute fidélité sur Figma
- Livraison du Jalon 2

## Semaine du 2 mars 2026
- Début du Jalon 3
- Révision de la méthode MERISE vu en cours
- Réalisation du MCD sur draw.io
- Réalisation du MLD sur draw.io

## Semaine du 16 mars 2026
- Rédaction du MPD (script SQL MySQL 8.0)
- Test du script dans phpMyAdmin : 10 tables validées
- Rédaction du document Jalon 3

## Semaine du 30 mars 2026
- Livraison du Jalon 3
- Début du Jalon 4
- Réalisation du diagramme de cas d'utilisation UML 2.5 sur draw.io
- 2 acteurs identifiés (Employé, Admin) avec relation de généralisation
- 10 cas d'utilisation couvrant toutes les fonctionnalités du CDCF

## Semaine du 13 avril 2026
- Réalisation des 3 diagrammes de séquence sur draw.io :
  - Enregistrer une arrivée de sachet (fragment alt JWT + données invalides)
  - Connexion JWT (fragment alt identifiants incorrects)
  - Changer le statut d'un sachet (PATCH + INSERT LOG)
- Réalisation du diagramme de classes UML 2.5 (9 classes + 3 enums)
- Rédaction du document Jalon 4

## Semaine du 27 avril 2026
- Mise en place du CI/CD via GitHub Actions
- Développement full-stack (frontend React + backend Symfony) : application fonctionnelle
- Correction bugs header dashboard (responsive mobile, fusion en un seul header, sticky + SmartSearch)
- Suppression référence sachet full-stack + fix labels Select MUI

## Semaine du 11 mai 2026
- Rédaction du document Jalon 5 : sécurité & tests
- Analyse de sécurité : CSRF (architecture JWT stateless), hachage bcrypt, protection brute force, conformité RGPD
- Réalisation de 2 audits de sécurité complets via Claude Code
- Correction des failles critiques identifiées à l'audit 1
- Génération d'un prompt consolidé pour les 13 findings medium/high de l'audit 2
- JWT token TTL configuré à 4h (`token_ttl: 14400`) pour couvrir une journée de travail

## Semaine du 25 mai 2026
- Livraison du Jalon 5
- Mise en place du CI/CD via GitHub Actions (jobs PHPUnit + Vite build)
- Ajout de l'entité `Espece` + migration `Version20260428115009`
- Mise à jour entité `Uv` : FK → Espece (NOT NULL), champs `nombrePlantParPlateaux`, `nombreGraineParMotte`
- Mise à jour entité `Plant` : FK → Espece (nullable)
- Endpoint `GET /api/especes/{id}/uvs` implémenté
- Frontend : dropdown cascadant Espèce → UV dans la modal "Utiliser des graines"
- Début Jalon 6 : analyse de la couverture PHPUnit (cible 80% — gap principal : `src/Controller/` à 0%)

## Semaine du 8 juin 2026
- Correction des CVE react-router : `npm audit fix` (0 vulnérabilités restantes)
- Résolution du conflit Git sur `main` (`git stash` / `pull --rebase` / `push`)

## Semaine du 19 juin 2026
- Ajout des boutons de recherche dans la modal "Ajouter un sachet" : layout `[Dropdown] [🔍] [+]`
- Création du composant réutilisable `SearchDialog` (filtrage client-side en temps réel)
- Correction bug affichage `null` sur les noms clients (`prenom_client` nullable) via `.filter(Boolean).join(' ')`

## Semaine du 25 juin 2026
- Livraison du Jalon 6
- Génération du document consolidé PDF/DOCX (7 chapitres couvrant les 6 sprints)
- Création et push du tag Git `v1.0` — "Version finale 1.0 - Livrable Jalon 6"
- Couverture PHPUnit atteinte : 80%+
