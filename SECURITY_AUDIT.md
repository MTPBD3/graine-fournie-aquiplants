# Rapport d'audit de sécurité — AQUIPLANTS

**Date** : 2026-05-22
**Périmètre** : Backend Symfony 7 · Frontend React 19 · Infra Docker Compose
**Auditeur** : Claude Sonnet 4.6 (analyse statique du code source)

---

## Résumé exécutif

| Niveau | Nombre |
|--------|--------|
| 🔴 Critique | 3 |
| ⚠️ Élevé | 5 |
| 🟡 Moyen | 5 |
| 🔵 Faible | 4 |

Les points les plus urgents sont : (1) le wildcard CORS sur tous les endpoints `/api/`, (2) l'absence de protection `ROLE_ADMIN` sur le CRUD utilisateurs, (3) les nombreuses CVE Symfony actives nécessitant une mise à jour vers 7.4.12+.

---

## 1. Secrets et variables d'environnement

### 1.1 `.env.dev` commité avec un APP_SECRET réel
**Statut** : ⚠️ Élevé
**Priorité** : Élevée

**Description** : `backend/.env.dev` est commité et contient un APP_SECRET réel (`f78c3d118228b308e7b54dc6358e66d4`). Symfony charge `.env.dev` en environnement `dev`, ce qui expose cette valeur à quiconque a accès au dépôt.

**Recommandation** : Supprimer le secret de ce fichier et le remplacer par un placeholder (identique à `backend/.env`). Stocker la valeur réelle dans `backend/.env.local`. Réécrire l'historique Git avec `git filter-repo` si le secret doit être révoqué.

---

### 1.2 Credentials dans `docker-compose.yml` (fichier commité)
**Statut** : ⚠️ Élevé
**Priorité** : Élevée

**Description** : Le fichier `docker-compose.yml` est commité avec des identifiants en clair :

```yaml
MYSQL_ROOT_PASSWORD: root
MYSQL_PASSWORD: aquiplants
JWT_PASSPHRASE: aquiplants
APP_SECRET: aquiplants_dev_secret_change_in_production
```

Ces valeurs sont visibles dans l'historique Git depuis le commit `154afee4`.

**Recommandation** : Utiliser des variables d'environnement shell ou un fichier `.env` à la racine (exclu du Git) pour alimenter docker-compose :
```yaml
environment:
  JWT_PASSPHRASE: ${JWT_PASSPHRASE}
  MYSQL_ROOT_PASSWORD: ${MYSQL_ROOT_PASSWORD}
```
Ajouter `.env` à la racine dans `.gitignore` et fournir un `.env.example` avec des placeholders.

---

### 1.3 Clés JWT et `.env.local` non committés
**Statut** : ✅ OK

**Description** : `backend/.gitignore` exclut correctement `/config/jwt/*.pem`, `/.env.local` et `/.env.*.local`. L'historique Git ne contient aucune trace de ces fichiers. Le commit `5de95e16` confirme que l'historique a été réécrit via `filter-branch` pour effacer des valeurs précédemment exposées dans `backend/.env`.

---

### 1.4 `frontend/.env` commité
**Statut** : 🔵 Faible
**Priorité** : Faible

**Description** : `frontend/.env` est commité avec `VITE_API_URL=http://localhost:8000`. Ce n'est pas un secret, mais les variables d'environnement Vite sont embedées dans le bundle JS. Pour un déploiement multi-environnement, utiliser uniquement `frontend/.env.local` (non commité).

**Recommandation** : Ne conserver dans `frontend/.env` que des valeurs non sensibles et valides pour tous les environnements (ou vider le fichier). Confirmer que `frontend/.env.development.local` (contenant l'IP LAN) reste bien ignoré par Git.

---

## 2. Authentification JWT

### 2.1 TTL du token non configuré explicitement
**Statut** : 🟡 Moyen
**Priorité** : Moyenne

**Description** : `config/packages/lexik_jwt_authentication.yaml` ne définit pas `token_ttl`. La valeur par défaut de LexikJWT est **3600 secondes (1 heure)**, ce qui est acceptable pour une application interne. Cependant, l'absence de configuration explicite ne documente pas ce choix.

**Recommandation** : Ajouter `token_ttl: 3600` (ou une valeur adaptée) dans `lexik_jwt_authentication.yaml` pour rendre la durée de vie explicite et auditable.

---

### 2.2 Déconnexion côté frontend uniquement (pas de blacklist JWT)
**Statut** : 🔵 Faible
**Priorité** : Faible

**Description** : La déconnexion (`AuthContext.jsx:51-56`) supprime le token du `localStorage` mais ne l'invalide pas côté serveur. C'est un comportement inhérent à JWT (stateless). Un token volé reste valide jusqu'à son expiration.

**Recommandation** : Pour une application sensible, implémenter une blacklist JWT côté serveur (Redis ou table de révocation), ou réduire le TTL. Pour un usage intranet avec 1h de TTL, le risque est acceptable.

---

### 2.3 JWT stocké dans localStorage (risque XSS)
**Statut** : 🟡 Moyen
**Priorité** : Moyenne

**Description** : `AuthContext.jsx:6` stocke le JWT dans `localStorage`. Le localStorage est accessible à tout JavaScript sur la page, ce qui expose le token en cas de XSS. Les cookies `HttpOnly` sont plus sûrs pour les tokens d'authentification.

**Recommandation** : Migrer vers des cookies `HttpOnly; Secure; SameSite=Strict`. LexikJWT supporte le mode cookie. Pour une application intranet sans données critiques, localStorage reste tolérable si le risque XSS est maîtrisé (CSP stricte).

---

### 2.4 Aucun token en dur dans le code source
**Statut** : ✅ OK

**Description** : Aucun JWT hardcodé trouvé dans le code frontend ou backend. Les tests utilisent `'fake-token'` uniquement dans les mocks de test.

---

## 3. Sécurité des endpoints API (Symfony)

### 3.1 CRITIQUE — `UtilisateurController` accessible à ROLE_EMPLOYE
**Statut** : 🔴 Critique
**Priorité** : Critique

**Description** : `backend/src/Controller/UtilisateurController.php` expose un CRUD complet sur `/api/utilisateurs` sans aucun contrôle de rôle (`#[IsGranted]` ou `denyAccessUnlessGranted`). La règle `security.yaml` protège uniquement le préfixe `/api/admin` avec `ROLE_ADMIN`, mais ce controller est sous `/api/utilisateurs`.

Conséquence : tout employé authentifié peut :
- **Lister tous les utilisateurs** (emails, noms, rôles) — `GET /api/utilisateurs`
- **Créer un compte ROLE_ADMIN** — `POST /api/utilisateurs` avec `"role": "ROLE_ADMIN"`
- **Modifier le rôle d'un utilisateur existant** — `PUT /api/utilisateurs/{id}`
- **Supprimer n'importe quel utilisateur** — `DELETE /api/utilisateurs/{id}`

C'est une **élévation de privilèges directe**.

**Recommandation** : Ajouter `#[IsGranted('ROLE_ADMIN')]` sur la classe ou sur chaque action sensible du `UtilisateurController` :
```php
#[Route('/api/utilisateurs')]
#[IsGranted('ROLE_ADMIN')]
class UtilisateurController extends AbstractController
```
Ou déplacer les routes sous `/api/admin/utilisateurs` pour exploiter la règle `security.yaml` existante.

---

### 3.2 Absence de validation des données entrantes (plusieurs controllers)
**Statut** : ⚠️ Élevé
**Priorité** : Élevée

**Description** : Le composant Symfony Validator n'est pas utilisé dans le projet. Plusieurs champs acceptent des valeurs arbitraires :

- `HistoGfDeposeeController::create()` : le champ `statut` accepte n'importe quelle chaîne (pas de contrôle contre les valeurs valides `en_attente|en_stock|epuise|range`). Des valeurs invalides peuvent corrompre la logique métier.
- `EmplacementController::assigner()` : `lettreEtagere` n'est pas validé contre `[A-D]`, `numeroEtage` contre `[1-4]`.
- `ClientController::create()` : aucune validation d'email ni de longueur des champs nom/prénom.
- `UtilisateurController::create()` : le champ `role` accepte n'importe quelle chaîne. Un rôle arbitraire peut être stocké en base et produire un comportement imprévisible.
- `GfClientController::update()` : pas de revalidation de `quantiteDisponible` (valeur négative possible).

**Recommandation** : Utiliser les attributs Symfony Validator (`#[Assert\Choice]`, `#[Assert\Length]`, `#[Assert\Email]`, `#[Assert\PositiveOrZero]`) sur les entités ou dans les controllers, et retourner une réponse 422 en cas d'erreur de validation.

---

### 3.3 Requêtes SQL brutes dans `StatistiquesController`
**Statut** : ✅ OK (paramétrisées correctement)

**Description** : `StatistiquesController.php:36` et `:101` utilisent `executeQuery()` avec des requêtes SQL brutes. Analyse :
- `/api/statistiques` : requête statique sans paramètre utilisateur. Pas de risque.
- `/api/stats/depots` : la variable `$periode` est validée par `match()` avant utilisation (seuls `1M`, `3M`, `6M` sont acceptés) et `$start` est passé via binding `:start`. Pas d'injection SQL possible.

---

### 3.4 Absence de rate limiting sur `/api/login`
**Statut** : 🟡 Moyen
**Priorité** : Moyenne

**Description** : Aucune configuration `login_throttling` trouvée dans `security.yaml` ou ailleurs. Le endpoint `/api/login` est vulnérable aux attaques par force brute sur les mots de passe.

**Recommandation** : Activer le composant Symfony RateLimiter (`symfony/rate-limiter`) et configurer `login_throttling` dans le firewall :
```yaml
login:
  login_throttling:
    max_attempts: 5
    interval: '15 minutes'
```

---

### 3.5 Absence de politique de mot de passe
**Statut** : 🔵 Faible
**Priorité** : Faible

**Description** : `UtilisateurController::create()` accepte n'importe quel mot de passe sans contrainte de longueur ou de complexité. Un mot de passe d'un seul caractère est accepté.

**Recommandation** : Ajouter `#[Assert\PasswordStrength]` (disponible depuis Symfony 6.3) ou une validation manuelle (longueur minimale 10 caractères).

---

## 4. Frontend React

### 4.1 `sanitize()` non appliqué sur toutes les pages avec saisie utilisateur
**Statut** : 🟡 Moyen
**Priorité** : Moyenne

**Description** : `sanitize()` est importé et utilisé dans `ArriveesSachetsPage.jsx` et `GestionUtilisateursPage.jsx`. Les pages `AlertesPage.jsx` et `GestionStocksPage.jsx` n'appliquent pas de sanitisation sur les données envoyées à l'API. React échappe automatiquement le HTML dans le rendu, ce qui limite le risque XSS côté affichage, mais les données non sanitisées atteignent la base de données et peuvent créer des problèmes si elles sont réutilisées dans d'autres contextes (exports, emails, logs).

**Recommandation** : Appliquer `sanitize()` sur tous les champs texte libres avant envoi à l'API, ou centraliser cette opération dans `apiRequest()`.

---

### 4.2 Aucun `console.log` en production
**Statut** : ✅ OK

**Description** : Aucun `console.log`, `console.error` ou `console.warn` trouvé dans le code source de production (`frontend/src/`), uniquement dans les fichiers de test.

---

### 4.3 JWT non exposé dans l'URL ou les logs
**Statut** : ✅ OK

**Description** : Le JWT est transmis uniquement via le header `Authorization: Bearer` dans `useApi.js` et `apiRequest()`. Il n'apparaît pas dans les paramètres d'URL ni dans les logs de la console.

---

### 4.4 Gestion des erreurs sans stack trace exposée
**Statut** : ✅ OK

**Description** : `useApi.js:69` affiche `err.message` (message générique comme `"Erreur 500"`), pas de stack trace. Les pages affichent ces messages via des `<Alert>` MUI. L'API Symfony retourne des messages structurés (`{'message': '...'}`) sans stack trace. Attention : en mode `APP_ENV: dev` (configuration actuelle Docker), Symfony peut retourner des détails d'erreur complets — voir point 5.3.

---

## 5. Configuration Docker

### 5.1 phpMyAdmin exposé sur le port 8080 sans restriction réseau
**Statut** : ⚠️ Élevé
**Priorité** : Élevée

**Description** : `docker-compose.yml:67-80` expose phpMyAdmin sur le port `8080` sur toutes les interfaces réseau, avec le mot de passe root hardcodé (`root`). En environnement de production ou accessible sur un réseau partagé, phpMyAdmin donne un accès direct et complet à la base de données MySQL.

**Recommandation** : En production, supprimer entièrement le service `phpmyadmin` du `docker-compose.yml`. En développement, restreindre l'accès au réseau local via `127.0.0.1:8080:80` ou utiliser un profil Docker Compose (`--profile tools`).

---

### 5.2 Port MySQL exposé sur toutes les interfaces réseau
**Statut** : 🟡 Moyen
**Priorité** : Moyenne

**Description** : `docker-compose.yml:13` mappe `"3307:3306"` sur toutes les interfaces réseau. Si le serveur est accessible depuis Internet, le port MySQL est exposé publiquement avec le password `aquiplants`.

**Recommandation** : Restreindre le binding à localhost : `"127.0.0.1:3307:3306"`. En production, supprimer complètement l'exposition du port MySQL (les containers communiquent via le réseau Docker interne sans port exposé).

---

### 5.3 `APP_ENV: dev` activé (erreurs détaillées en production potentielle)
**Statut** : ⚠️ Élevé
**Priorité** : Élevée

**Description** : `docker-compose.yml:38` définit `APP_ENV: dev`. En mode `dev`, Symfony active le profiler, les messages d'erreur détaillés (stack traces complètes, variables d'environnement) et des performances dégradées. Si ce fichier est utilisé en production, les erreurs exposent la structure interne de l'application.

**Recommandation** : Créer un `docker-compose.prod.yml` avec `APP_ENV: prod` et `APP_DEBUG: "0"`. Ne jamais déployer avec le fichier de développement.

---

### 5.4 Conteneurs tournant en tant que root
**Statut** : 🟡 Moyen
**Priorité** : Moyenne

**Description** : Les Dockerfiles `docker/symfony/Dockerfile` et `docker/react/Dockerfile` ne définissent pas de directive `USER`. Les processus s'exécutent donc en tant que `root` dans les conteneurs. En cas d'exploitation d'une faille applicative, l'attaquant obtient des privilèges root dans le conteneur.

**Recommandation** : Ajouter un utilisateur non-root dans les Dockerfiles. Pour Nginx/PHP-FPM, utiliser l'utilisateur `www-data` existant :
```dockerfile
USER www-data
```

---

### 5.5 Xdebug installé dans l'image de production
**Statut** : 🔵 Faible
**Priorité** : Faible

**Description** : `docker/symfony/Dockerfile:20` installe Xdebug via `pecl install xdebug`. Bien que configuré en mode `coverage` uniquement (`xdebug.mode=coverage`), Xdebug augmente la surface d'attaque et dégrade les performances. Il ne devrait pas être présent dans une image de production.

**Recommandation** : Utiliser un build multi-stage dans le Dockerfile — installer Xdebug uniquement dans le stage `dev`, pas dans le stage `prod`.

---

## 6. CORS

### 6.1 CRITIQUE — Wildcard CORS sur tous les endpoints `/api/`
**Statut** : 🔴 Critique
**Priorité** : Critique

**Description** : `config/packages/nelmio_cors.yaml:9-11` configure une règle spécifique qui **écrase** la valeur de `CORS_ALLOW_ORIGIN` pour toutes les routes `/api/` :

```yaml
paths:
  '^/api/':
    allow_origin: ['*']
```

Cela signifie que **n'importe quel site web peut effectuer des requêtes vers l'API**. Cette configuration annule complètement la protection CORS. La variable d'environnement `CORS_ALLOW_ORIGIN` définie dans `backend/.env` et `docker-compose.yml` est ignorée pour le chemin `/api/`.

**Recommandation** : Supprimer la surcharge dans `paths` ou la remplacer par la valeur de l'env var :
```yaml
nelmio_cors:
  defaults:
    origin_regex: true
    allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
    allow_methods: ['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE']
    allow_headers: ['Content-Type', 'Authorization']
    expose_headers: ['Link']
    max_age: 3600
  paths:
    '^/api/':
      origin_regex: true
      allow_origin: ['%env(CORS_ALLOW_ORIGIN)%']
```

---

## 7. Headers HTTP

### 7.1 Absence de headers de sécurité HTTP
**Statut** : ⚠️ Élevé
**Priorité** : Élevée

**Description** : La configuration Nginx (`docker/symfony/nginx.conf`) ne définit aucun des headers de sécurité standards :

| Header | Impact de l'absence |
|--------|---------------------|
| `X-Content-Type-Options: nosniff` | MIME sniffing possible par les navigateurs |
| `X-Frame-Options: SAMEORIGIN` | Clickjacking possible (intégration en iframe) |
| `Content-Security-Policy` | XSS sans restriction si une injection HTML se produit |
| `Strict-Transport-Security` | Downgrade HTTPS→HTTP possible |
| `Referrer-Policy` | URL complète envoyée dans les Referer externes |
| `Permissions-Policy` | Accès caméra/micro/géolocalisation non restreint |

**Recommandation** : Ajouter dans `nginx.conf` (bloc `server`) :
```nginx
add_header X-Content-Type-Options "nosniff" always;
add_header X-Frame-Options "SAMEORIGIN" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
add_header Content-Security-Policy "default-src 'self'; script-src 'self'; object-src 'none';" always;
# Uniquement si HTTPS est activé :
add_header Strict-Transport-Security "max-age=31536000; includeSubDomains" always;
```

---

## 8. RGPD

### 8.1 Données personnelles dans les logs d'audit
**Statut** : 🔵 Faible
**Priorité** : Faible

**Description** : L'application collecte et stocke les données personnelles suivantes :

| Entité | Données | Table |
|--------|---------|-------|
| `Utilisateur` | email, nom, prénom, rôle, mot de passe hashé | `utilisateur` |
| `Client` | nom, prénom | `client` |
| `Log` | email dans le champ `detail`, date, action | `log` |

`LogService` enregistre des données personnelles directement dans le champ `detail` :
```php
'Utilisateur ' . $u->getEmail() . ' créé'    // email dans log
'Utilisateur ' . $email . ' supprimé'         // email dans log
```
Il n'existe pas de politique de rétention ni de mécanisme de purge des logs.

**Recommandation** : Remplacer l'email dans les logs par un identifiant anonymisé (ex : ID utilisateur). Implémenter une politique de rétention (suppression des logs de plus de 12 mois). Rédiger une politique de confidentialité conforme au RGPD.

---

### 8.2 Aucune donnée personnelle dans les logs Symfony (`var/log/`)
**Statut** : ✅ OK

**Description** : Aucune configuration Monolog custom trouvée. Les logs Symfony par défaut contiennent uniquement des informations système (requêtes HTTP, erreurs), pas de données personnelles métier. Xdebug est configuré en mode `coverage` (pas de traces à la volée).

---

## 9. Dépendances

### 9.1 CRITIQUE — 10 CVE actives dans les packages Symfony (< 7.4.12)
**Statut** : 🔴 Critique
**Priorité** : Critique

**Description** : `composer audit` a détecté **10 vulnérabilités actives** dans les packages Symfony installés (version < 7.4.12). Toutes sont corrigées dans Symfony >= 7.4.12 :

| Package | CVE | Description | Sévérité |
|---------|-----|-------------|----------|
| `symfony/http-kernel` | CVE-2026-45075 | Requête HTTP `HEAD` bypasse `#[IsGranted]` et `#[IsCsrfTokenValid]` | **Critique** |
| `symfony/security-http` | CVE-2026-45075 | Identique — bypass `#[IsGranted]` via HEAD | **Critique** |
| `symfony/security-http` | CVE-2026-45063 | Usurpation d'identité via regex DN non ancrée (X.509) | Élevée |
| `symfony/security-http` | CVE-2026-45069 | OidcTokenHandler accepte des JWT sans `aud`/`iss`/`exp` | Élevée |
| `symfony/security-http` | CVE-2026-45074 | CAS service URL dérivée du Host header (ticket replay) | Élevée |
| `symfony/runtime` | CVE-2026-46626 | `APP_ENV`/`APP_DEBUG` overridables via requêtes web | Élevée |
| `symfony/cache` | CVE-2026-45073 | SQL Injection dans `PdoAdapter::doClear()` | Élevée |
| `symfony/routing` | CVE-2026-45065 | Injection d'URL via regex non ancrée | Élevée |
| `symfony/dom-crawler` | CVE-2026-45071 | XXE / Local File Disclosure dans `addXmlContent()` | Élevée |
| `symfony/yaml` | CVE-2026-45304/45305/45133 | DoS : mémoire exponentielle, ReDoS, stack exhaustion | Élevée |

**Impact direct sur ce projet** : CVE-2026-45075 affecte directement l'application — une requête `HEAD` vers `/api/logs` pourrait contourner le `denyAccessUnlessGranted('ROLE_ADMIN')` dans `LogController`. CVE-2026-46626 est critique car `APP_ENV: dev` est actuellement actif dans docker-compose.

**Recommandation** : Mettre à jour immédiatement :
```bash
docker exec -it gf_symfony composer update "symfony/*" --with-all-dependencies
```
Vérifier la compatibilité et ré-exécuter la suite de tests avant déploiement.

---

### 9.2 Aucune vulnérabilité dans les dépendances frontend
**Statut** : ✅ OK

**Description** : `npm audit` dans `frontend/` retourne **0 vulnerability**. Les dépendances JS (React 19, MUI v9, Vite 8, Recharts 3) sont à jour et sans CVE connue.

---

## Tableau récapitulatif des priorités d'action

| # | Catégorie | Problème | Statut | Priorité |
|---|-----------|----------|--------|----------|
| 3.1 | Auth API | CRUD utilisateurs accessible à ROLE_EMPLOYE (escalade de privilèges) | 🔴 Critique | Critique |
| 6.1 | CORS | `allow_origin: ['*']` sur tous les endpoints `/api/` | 🔴 Critique | Critique |
| 9.1 | Dépendances | 10 CVE Symfony actives dont bypass `#[IsGranted]` via HEAD | 🔴 Critique | Critique |
| 1.2 | Secrets | Credentials Docker Compose hardcodés dans le repo Git | ⚠️ Élevé | Élevée |
| 1.1 | Secrets | `APP_SECRET` réel dans `backend/.env.dev` commité | ⚠️ Élevé | Élevée |
| 5.1 | Docker | phpMyAdmin exposé publiquement avec password root | ⚠️ Élevé | Élevée |
| 5.3 | Docker | `APP_ENV: dev` activé (stack traces, profiler en production) | ⚠️ Élevé | Élevée |
| 3.2 | API | Absence de validation des données entrantes (statut, rôle, email…) | ⚠️ Élevé | Élevée |
| 7.1 | Headers | Aucun header de sécurité HTTP (CSP, HSTS, X-Frame…) | ⚠️ Élevé | Élevée |
| 2.3 | JWT | JWT stocké dans localStorage (risque XSS) | 🟡 Moyen | Moyenne |
| 3.4 | API | Pas de rate limiting sur `/api/login` (brute force) | 🟡 Moyen | Moyenne |
| 4.1 | Frontend | `sanitize()` non appliqué sur toutes les pages avec saisie | 🟡 Moyen | Moyenne |
| 5.2 | Docker | Port MySQL exposé sur toutes les interfaces réseau | 🟡 Moyen | Moyenne |
| 5.4 | Docker | Conteneurs tournant en tant que root | 🟡 Moyen | Moyenne |
| 2.1 | JWT | TTL du token non configuré explicitement (défaut 1h non documenté) | 🔵 Faible | Faible |
| 1.4 | Secrets | `frontend/.env` commité avec `VITE_API_URL` | 🔵 Faible | Faible |
| 3.5 | API | Aucune politique de complexité des mots de passe | 🔵 Faible | Faible |
| 5.5 | Docker | Xdebug installé dans l'image (devrait être limité au stage dev) | 🔵 Faible | Faible |
| 8.1 | RGPD | Emails dans les logs d'audit, pas de politique de rétention | 🔵 Faible | Faible |

---

*Ce rapport est issu d'une analyse statique du code source uniquement. Des tests dynamiques (pentesting, fuzzing, scan réseau) pourraient révéler des vulnérabilités supplémentaires non détectables par analyse statique.*
