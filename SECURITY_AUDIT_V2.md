# SECURITY_AUDIT_V2.md — AQUIPLANTS

**Date** : 2026-05-22  
**Auditeur** : Analyse statique + tests dynamiques (appli en cours d'exécution)  
**Périmètre** : Symfony 7.4.12 / React 19 / Docker Compose — post-correction des 16 failles de l'audit V1  
**Environnement** : `APP_ENV=prod`, conteneurs `gf_symfony`, `gf_react`, `gf_mysql`, `gf_phpmyadmin`

---

## Section A — Vérification des corrections du premier audit

| # | Correction attendue | Statut | Détails |
|---|---------------------|--------|---------|
| 1 | CORS wildcard supprimé | ✅ Corrigé | `nelmio_cors.yaml` utilise `'%env(CORS_ALLOW_ORIGIN)%'` dans `defaults` ET `paths` |
| 2 | `#[IsGranted('ROLE_ADMIN')]` sur `UtilisateurController` | ✅ Corrigé | `HEAD /api/utilisateurs` → 401 sans token ; `ROLE_EMPLOYE` → 403 |
| 3 | CVE Symfony → `composer audit` = 0 vulnérabilité | ✅ Corrigé | Connexion réseau indisponible depuis Docker pour revalider, mais la mise à jour vers 7.4.12+ a été effectuée lors de l'audit V1 et validée à ce moment |
| 4 | Secrets hors `docker-compose.yml` | ✅ Corrigé | Toutes les valeurs utilisent `${VAR}` via `.env.docker` |
| 5 | `APP_SECRET` vidé de `.env.dev` | ✅ Corrigé | `APP_SECRET=` (vide) dans `backend/.env.dev` |
| 6 | phpMyAdmin sur `127.0.0.1:8080` uniquement | ✅ Corrigé | `"127.0.0.1:8080:80"` confirmé dans `docker-compose.yml` |
| 7 | `APP_ENV: prod` dans Docker | ✅ Corrigé | `APP_ENV: prod` dans `docker-compose.yml` ; `APP_DEBUG` absent = `false` |
| 8 | Headers HTTP de sécurité ajoutés | ⚠️ Partiellement corrigé | Headers ajoutés dans `nginx.conf`, MAIS **doublons conflictuels** (voir B-06) : deux `CSP` différents, deux `X-Frame-Options` différents, `X-Powered-By: PHP/8.2.31` toujours exposé |
| 9 | Port MySQL sur `127.0.0.1:3307` | ✅ Corrigé | `"127.0.0.1:3307:3306"` confirmé |
| 10 | Rate limiting sur `/api/login` (→ 429 après 5 échecs) | 🔴 Non corrigé | `login_throttling` configuré dans `security.yaml`, mais **aucun 429 déclenché** après 12 tentatives consécutives (voir B-01) |
| 11 | `sanitize()` sur toutes les pages | ✅ Corrigé | `ArriveesSachetsPage.jsx` et `GestionUtilisateursPage.jsx` importent et appliquent `sanitize()` |
| 12 | TTL JWT explicite à 3600s | ✅ Corrigé | `token_ttl: 3600` dans `lexik_jwt_authentication.yaml` |
| 13 | Politique mot de passe 10 caractères min | ✅ Corrigé | `validatePassword()` dans `UtilisateurController.php` : min 10 chars, 1 majuscule, 1 chiffre |
| 14 | Logs anonymisés (ID au lieu d'email) | ✅ Corrigé | Les messages de log dans `UtilisateurController` utilisent `'Utilisateur #' . $u->getId()`. Nuance : `LogController` expose toujours `utilisateur.email` dans la réponse de liste (voir B-08) |

**Résumé Section A** : 11 corrections effectives, 1 partielle, 1 non corrigée, 1 non revalidable.

---

## Section B — Nouvelles failles identifiées

---

### B-01 — ⚠️ Élevé — Rate limiting (login_throttling) non fonctionnel

**Description** : La configuration `login_throttling` dans `security.yaml` ne produit aucun effet. 12 tentatives de connexion échouées consécutives retournent toutes 401 (jamais 429).

**Preuve** :
```
# 12 tentatives avec mot de passe incorrect
for i in {1..12}; do curl -s -o /dev/null -w "Tentative $i: %{http_code}\n" \
  POST http://localhost:8000/api/login -d '{"email":"x@test.fr","password":"WRONG"}'; done
→ Tentative 1: 401 … Tentative 12: 401  # jamais 429
```
Diagnostic : `php bin/console debug:config framework rate_limiter` retourne `limiters: {}`. Le pool `cache.rate_limiter` est configuré sur `FilesystemAdapter` avec le chemin `/var/www/html/var/share/prod/pools/app`, mais ce répertoire n'existe pas dans le conteneur. Le `FilesystemAdapter` échoue silencieusement, ce qui désactive le throttling.

**Impact** : Brute force de mots de passe sans limitation.

**Recommandation** :
1. Créer le répertoire de cache ou utiliser un pool dédié :
```yaml
# config/packages/framework.yaml
framework:
    cache:
        pools:
            cache.rate_limiter:
                adapter: cache.adapter.filesystem
                default_lifetime: 900
```
2. Vérifier que le répertoire `var/cache/prod/pools/` est accessible en écriture dans le conteneur.
3. Valider avec `curl` que la 6e tentative retourne bien 429.

---

### B-02 — ⚠️ Élevé — Contournement du contrôle d'accès sur `/api/alertes`

**Description** : `AlertesController` ne déclare aucun `#[IsGranted]` ni `denyAccessUnlessGranted()`. Le frontend restreint la page `/alertes` à `ROLE_ADMIN` via `PrivateRoute`, mais le backend n'applique pas cette restriction.

**Preuve** :
```bash
USER_TOKEN=$(curl -s POST /api/login -d '{"email":"testuser@aquiplants.fr","password":"user"}' | jq -r .token)
curl -s -o /dev/null -w "%{http_code}" GET /api/alertes -H "Authorization: Bearer $USER_TOKEN"
→ 200   # attendu : 403
```
Fichier concerné : `backend/src/Controller/AlertesController.php:15`.

**Impact** : Un employé peut interroger directement l'endpoint d'alertes avec son token JWT, contournant la restriction frontend.

**Recommandation** :
```php
// AlertesController.php
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/api/alertes', methods: ['GET'])]
#[IsGranted('ROLE_ADMIN')]
public function index(HistoGfDeposeeRepository $repo): JsonResponse
```

---

### B-03 — ⚠️ Élevé — Privilèges MySQL excessifs (`GRANT ALL`)

**Description** : L'utilisateur MySQL `aquiplants` dispose de `ALL PRIVILEGES` sur `aquiplants_db`, incluant `DROP TABLE`, `ALTER TABLE`, `CREATE`, `GRANT OPTION`.

**Preuve** :
```sql
SHOW GRANTS FOR 'aquiplants'@'%';
→ GRANT ALL PRIVILEGES ON `aquiplants_db`.* TO `aquiplants`@`%`
```

**Impact** : En cas de compromission de l'application (injection SQL, RCE), l'attaquant dispose d'un accès complet à la base, y compris la suppression irréversible de toutes les données et l'escalade vers d'autres bases.

**Recommandation** : Restreindre aux seuls privilèges nécessaires :
```sql
REVOKE ALL PRIVILEGES ON aquiplants_db.* FROM 'aquiplants'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON aquiplants_db.* TO 'aquiplants'@'%';
FLUSH PRIVILEGES;
```
Conserver un utilisateur séparé avec `ALTER`, `CREATE`, `DROP` uniquement pour les migrations (à exécuter manuellement ou via un CI dédié).

---

### B-04 — 🟡 Moyen — Headers de sécurité en double et conflictuels

**Description** : Symfony (security bundle) et nginx ajoutent **tous les deux** des headers de sécurité, créant des doublons avec des valeurs contradictoires.

**Preuve** (`curl -I http://localhost:8000/api/gf-clients -H "Authorization: Bearer $TOKEN"`) :
```http
Content-Security-Policy: default-src 'self'          ← ajouté par Symfony
X-Frame-Options: DENY                                 ← ajouté par Symfony
X-Content-Type-Options: nosniff
Referrer-Policy: strict-origin-when-cross-origin
X-Content-Type-Options: nosniff                       ← dupliqué par nginx
X-Frame-Options: SAMEORIGIN                           ← conflictuel (DENY vs SAMEORIGIN)
Referrer-Policy: strict-origin-when-cross-origin      ← dupliqué
Permissions-Policy: camera=(), microphone=(), geolocation=()
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; object-src 'none';  ← conflictuel
```

Lorsqu'un navigateur reçoit deux headers `X-Frame-Options` avec des valeurs différentes (`DENY` et `SAMEORIGIN`), ou deux `Content-Security-Policy`, le comportement est imprévisible et peut annuler la protection.

**Recommandation** : Désactiver les headers ajoutés par Symfony pour les laisser exclusivement à nginx. Créer `config/packages/security.yaml` → vérifier les `headers` ou configurer `nelmio_security` si installé. Alternativement, supprimer les directives `add_header` dans `nginx.conf` pour les headers que Symfony gère déjà, et ne conserver dans nginx que les headers que Symfony n'ajoute pas (`Permissions-Policy`, `Referrer-Policy`).

---

### B-05 — 🟡 Moyen — `X-Powered-By: PHP/8.2.31` exposé

**Description** : Tous les headers de réponse incluent `X-Powered-By: PHP/8.2.31`, révélant la version exacte du moteur PHP.

**Preuve** :
```http
HTTP/1.1 200 OK
X-Powered-By: PHP/8.2.31
Server: nginx
```

**Impact** : Facilite le ciblage de vulnérabilités spécifiques à PHP 8.2.31.

**Recommandation** : Supprimer ce header dans la configuration PHP-FPM ou dans nginx :
```nginx
# nginx.conf
fastcgi_hide_header X-Powered-By;
```
Ou via `php.ini` : `expose_php = Off`.

---

### B-06 — 🟡 Moyen — `APP_SECRET` non aléatoire (secret de développement en production)

**Description** : `APP_SECRET` est défini à `aquiplants_dev_secret_change_in_production` — une phrase mémorisable et non un token aléatoire, avec un commentaire explicite indiquant qu'il doit être changé en production.

**Preuve** :
```
docker exec gf_symfony php -r "echo getenv('APP_SECRET');"
→ aquiplants_dev_secret_change_in_production
```
Longueur : 42 chars, entropie estimée ~173 bits (calcul de Shannon), mais la valeur est une phrase prévisible qui se retrouve dans `.env.docker.example`, les commits git, et potentiellement dans des configs partagées.

**Impact** : `APP_SECRET` est utilisé par Symfony pour signer les cookies CSRF, les tokens de mémorisation, les liens de réinitialisation. Un secret prévisible permet leur forgeage.

**Recommandation** : Générer un secret aléatoire de 32 bytes hex avant tout déploiement en production :
```bash
php -r "echo bin2hex(random_bytes(32));"
# Mettre à jour APP_SECRET dans .env.docker (non commité)
```

---

### B-07 — 🟡 Moyen — `HistoGfDeposeeController` — exception non gérée sur date invalide

**Description** : `new \DateTime($data['dateReception'] ?? 'now')` dans `HistoGfDeposeeController::create()` lève une exception non interceptée si la date est malformée, retournant une page 500 HTML.

**Preuve** :
```bash
curl -s -w "\nHTTP: %{http_code}" POST /api/histo-gf-deposees \
  -H "Authorization: Bearer $TOKEN" \
  -d '{"idGfClient":1,"quantiteDeposee":10,"dateReception":"NOT_A_DATE","statut":"a_traiter"}'
→ HTTP: 500 (page HTML "Internal Server Error")
```
Fichier concerné : `backend/src/Controller/HistoGfDeposeeController.php:66`.

**Impact** : En mode prod, la 500 ne révèle pas de stack trace, mais peut être utilisé pour du déni de service partiel (l'endpoint crashe de façon contrôlée). En mode dev, le stack trace complet est exposé.

**Recommandation** :
```php
try {
    $date = new \DateTime($data['dateReception'] ?? 'now');
} catch (\Exception $e) {
    return $this->json(['message' => 'Format de date invalide (attendu: Y-m-d)'], 400);
}
```

---

### B-08 — 🟡 Moyen — `GfClientController::create()` expose les messages d'exception

**Description** : Le bloc `catch (\Throwable $e)` dans `GfClientController::create()` retourne `$e->getMessage()` directement dans la réponse JSON.

**Preuve** :
```php
// GfClientController.php:271
} catch (\Throwable $e) {
    return $this->json(['error' => $e->getMessage()], 500);
}
```
Si une contrainte d'unicité ou une erreur Doctrine est levée après les validations initiales, la réponse exposera des informations sur le schéma de base de données (noms de tables, colonnes, contraintes).

**Impact** : Reconnaissance facilitée de la structure de base de données.

**Recommandation** :
```php
} catch (\Throwable $e) {
    return $this->json(['message' => 'Erreur lors de la création du sachet'], 500);
}
```

---

### B-09 — 🔵 Faible — `LogController` expose les emails utilisateurs dans les réponses

**Description** : Bien que les messages de log d'audit (champ `detail`) utilisent désormais l'ID plutôt que l'email, `LogController::index()` inclut `utilisateur.email` dans chaque entrée de log retournée.

**Preuve** : `LogController.php:27-31` retourne `'email' => $l->getUtilisateur()->getEmail()`.

**Impact** : Les emails des utilisateurs qui ont effectué des actions sont exposés dans l'API de logs, accessible uniquement par `ROLE_ADMIN`. Risque RGPD limité (accès admin uniquement), mais les emails de tous les utilisateurs actifs peuvent être extraits par un administrateur malveillant.

**Recommandation** : Supprimer le champ `email` de la réponse, ou le remplacer par `'id'` et `'nom'`/`'prenom'` uniquement.

---

### B-10 — 🔵 Faible — `EmplacementController` — validation des coordonnées d'étagère absente

**Description** : `EmplacementController::assigner()` accepte `lettreEtagere` (toute chaîne) et `numeroEtage` (tout entier) sans validation contre les valeurs autorisées `['A','B','C','D']` et `[1,2,3,4]`.

**Preuve** : Appel avec `{"lettreEtagere":"Z","numeroEtage":99}` crée un emplacement `Z-99` sans erreur.

**Impact** : Pollution de la base de données avec des emplacements invalides, affichage incorrect dans la grille de stocks, comportement imprévisible de l'UI.

**Recommandation** :
```php
if (!in_array($lettreEtagere, ['A','B','C','D'], true) || !in_array($numeroEtage, [1,2,3,4], true)) {
    return $this->json(['message' => 'Emplacement invalide. Étagère: A-D, Étage: 1-4'], 400);
}
```

---

### B-11 — 🔵 Faible — Images Docker sans version patch épinglée

**Description** :
- `phpmyadmin/phpmyadmin` — **aucun tag** → pull `latest` à chaque build, comportement non reproductible
- `php:8.2-fpm` — version mineure non épinglée (pas de `8.2.31-fpm`)
- `node:20-alpine` — version majeure uniquement (pas de `20.x.y-alpine`)

**Impact** : En cas de nouvelle version contenant une régression ou une vulnérabilité, un rebuild déploie automatiquement la version compromise sans contrôle.

**Recommandation** : Épingler toutes les images à leur version patch exacte :
```yaml
image: phpmyadmin/phpmyadmin:5.2.1
```
```dockerfile
FROM php:8.2.31-fpm AS base
FROM node:20.19.2-alpine
```

---

### B-12 — 🔵 Faible — `PrivateRoute` ne valide pas l'expiration du token JWT côté client

**Description** : `PrivateRoute.jsx` protège les routes en vérifiant uniquement la présence de `token` dans le state React (initialisé depuis `localStorage`). Un token expiré ou invalidé côté serveur reste valide pour accéder aux pages protégées jusqu'au premier appel API.

**Preuve** : `PrivateRoute.jsx:7` — `if (!token)` — absence de décodage du payload pour vérifier `exp`.

**Impact** : Après expiration du JWT (1 heure), un utilisateur peut continuer à naviguer dans l'application jusqu'à ce qu'un appel API échoue avec 401. Ce n'est pas un bypass de sécurité (les données ne sont pas chargées), mais l'UX est dégradée et confuse.

**Recommandation** :
```jsx
// PrivateRoute.jsx
function isTokenExpired(token) {
  try {
    const payload = JSON.parse(atob(token.split('.')[1]));
    return payload.exp * 1000 < Date.now();
  } catch {
    return true;
  }
}

if (!token || isTokenExpired(token)) {
  return <Navigate to="/" replace />;
}
```

---

### B-13 — 🔵 Faible — Serveur Vite React exposé sur `0.0.0.0:3000` (toutes interfaces)

**Description** : Le conteneur `gf_react` expose le port 3000 sur toutes les interfaces réseau (`HostIp: ""`), rendant le serveur Vite de développement accessible depuis le réseau local.

**Preuve** : `docker inspect gf_react` → `"3000/tcp":[{"HostIp":"","HostPort":"3000"}]`.

**Impact** : Le serveur Vite de développement n'est pas conçu pour l'exposition publique — il peut révéler des informations de développement. Acceptable pour un réseau local isolé.

**Recommandation** : Restreindre à localhost si l'accès réseau n'est pas requis :
```yaml
# docker-compose.yml
ports:
  - "127.0.0.1:3000:3000"
```

---

## Vérifications sans faille identifiée

| Test | Résultat |
|------|----------|
| JWT forgé (payload modifié, signature invalide) | ✅ 401 |
| JWT expiré | ✅ 401 |
| JWT signé avec mauvaise clé | ✅ 401 |
| TRACE sur endpoints API | ✅ 405 |
| Profiler (`/_profiler`, `/_wdt`) | ✅ 404 |
| `APP_DEBUG` en production | ✅ `false` |
| npm audit (dépendances JS) | ✅ 0 vulnérabilité |
| Source maps en production | ✅ Non générées (défaut Vite) |
| SQL injection via `?search=` | ✅ Paramètre lié (pas de vulnérabilité) |
| Mass assignment (passer `id`, `role` dans POST) | ✅ Champs ignorés par Doctrine |
| Quantité négative sur sachet | ✅ Rejetée avec 400 |
| Méthodes HTTP non autorisées (PUT sur liste, PATCH sur ressource) | ✅ 405 |
| `/api/login` HEAD → méthode non autorisée | ✅ 405 |
| `/api/meteo` sans token | ✅ 401 |
| Upload de fichiers | ✅ Aucun endpoint d'upload |

---

## Tableau récapitulatif — Failles restantes

| ID | Sévérité | Titre | Fichier(s) concerné(s) |
|----|----------|-------|------------------------|
| B-01 | ⚠️ Élevé | Rate limiting non fonctionnel | `security.yaml`, `framework.yaml` (cache) |
| B-02 | ⚠️ Élevé | `/api/alertes` accessible à `ROLE_EMPLOYE` | `AlertesController.php:15` |
| B-03 | ⚠️ Élevé | MySQL `GRANT ALL` — privilèges excessifs | Configuration MySQL / `docker-compose.yml` |
| B-04 | 🟡 Moyen | Headers de sécurité en double et conflictuels | `nginx.conf`, Symfony security bundle |
| B-05 | 🟡 Moyen | `X-Powered-By: PHP/8.2.31` exposé | `nginx.conf` ou `php.ini` |
| B-06 | 🟡 Moyen | `APP_SECRET` non aléatoire (secret dev) | `.env.docker` |
| B-07 | 🟡 Moyen | Date invalide → 500 non géré | `HistoGfDeposeeController.php:66` |
| B-08 | 🟡 Moyen | Exception leakage dans `create()` | `GfClientController.php:271` |
| B-09 | 🔵 Faible | `LogController` expose les emails | `LogController.php:27` |
| B-10 | 🔵 Faible | Validation d'emplacement absente | `EmplacementController.php:99` |
| B-11 | 🔵 Faible | Images Docker non épinglées | `docker-compose.yml`, Dockerfiles |
| B-12 | 🔵 Faible | PrivateRoute ne vérifie pas l'expiration JWT | `PrivateRoute.jsx:7` |
| B-13 | 🔵 Faible | React exposé sur toutes interfaces réseau | `docker-compose.yml` |

**Total** : 3 Élevé · 5 Moyen · 5 Faible

---

## Priorité de correction

1. **[Immédiat]** B-01 : Rate limiting — corriger la configuration du cache pour que le throttling soit opérationnel.
2. **[Immédiat]** B-02 : Ajouter `#[IsGranted('ROLE_ADMIN')]` sur `AlertesController`.
3. **[Avant déploiement prod]** B-03 : Réduire les privilèges MySQL.
4. **[Avant déploiement prod]** B-06 : Générer un `APP_SECRET` aléatoire.
5. **[Court terme]** B-04 + B-05 : Corriger les doublons de headers et supprimer `X-Powered-By`.
6. **[Court terme]** B-07 + B-08 : Validation de date et nettoyage du bloc catch.
7. **[Backlog]** B-09 à B-13 : corrections mineures / hardening.
