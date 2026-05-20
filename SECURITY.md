# Sécurité — Graine Fournie AQUIPLANTS

## 1. Authentification JWT

- **Bibliothèque** : `lexik/jwt-authentication-bundle` v3.
- **Endpoint** : `POST /api/login` — public, reçoit `{ email, password }` en JSON.
- **Algorithme** : RS256 (clés RSA 4096 bits asymétriques).
- **Clés** : `backend/config/jwt/private.pem` (signée) et `public.pem` (vérification). Ces fichiers sont **gitignorés** ; ils doivent être générés à l'installation.
- **Expiration** : 3 600 secondes (1 heure) — valeur par défaut Lexik, non surchargée.
- **Stockage côté client** : le token est stocké dans `localStorage` (`jwt_token`). Acceptable pour une SPA interne ; un cookie `HttpOnly` offrirait une meilleure protection contre le XSS en production publique.
- **Toutes les routes** `/api/*` (hors `/api/login`) requièrent un token valide (`ROLE_EMPLOYE` minimum).

## 2. Gestion des rôles

| Rôle | Accès |
|---|---|
| `ROLE_EMPLOYE` | Routes `/api/*` standards (sachets, stocks, statistiques) |
| `ROLE_ADMIN` | Hiérarchiquement supérieur — inclut `ROLE_EMPLOYE` + gestion utilisateurs, alertes |

Configuré dans `backend/config/packages/security.yaml` :
```yaml
role_hierarchy:
    ROLE_ADMIN: [ROLE_EMPLOYE]
```

Valeur stockée en base : `admin` ou `employe`. La méthode `getRoles()` fait le mapping vers `ROLE_ADMIN` / `ROLE_EMPLOYE`.

## 3. Protection XSS

- **Frontend** : `frontend/src/utils/sanitize.js` échappe les caractères `< > " ' & \`` avant toute insertion dans le DOM ou envoi vers l'API.
- Utilisé systématiquement dans `ArriveesSachetsPage` et `GestionUtilisateursPage` avant les appels `apiRequest`.
- **Backend** : Doctrine ORM utilise des requêtes préparées avec paramètres liés — aucune concaténation SQL directe.

## 4. CORS

Configuré via `nelmio/cors-bundle`. La valeur de `CORS_ALLOW_ORIGIN` est injectée par variable d'environnement dans `docker-compose.yml` :

```
CORS_ALLOW_ORIGIN: '^https?://(localhost|127\.0\.0\.1|192\.168\.\d+\.\d+)(:[0-9]+)?$'
```

Seuls `localhost`, `127.0.0.1` et les adresses LAN `192.168.x.x` sont autorisés. À restreindre au domaine de production lors du déploiement.

## 5. Headers de sécurité HTTP

Ajoutés sur **toutes les réponses** par `SecurityHeadersListener` (écoute `kernel.response`) :

| Header | Valeur | Effet |
|---|---|---|
| `Content-Security-Policy` | `default-src 'self'` | Bloque les ressources d'origines externes |
| `X-Frame-Options` | `DENY` | Interdit l'intégration dans une `<iframe>` (clickjacking) |
| `X-Content-Type-Options` | `nosniff` | Empêche le MIME-sniffing |
| `Referrer-Policy` | `strict-origin-when-cross-origin` | Limite les informations de référent sur les requêtes cross-origin |

## 6. Points non couverts / améliorations futures

| Point | Priorité | Description |
|---|---|---|
| Cookie `HttpOnly` pour le JWT | Haute | Remplacer `localStorage` par un cookie `HttpOnly; Secure; SameSite=Strict` pour éliminer l'exposition du token au JavaScript |
| Refresh token | Moyenne | Mettre en place un mécanisme de renouvellement silencieux (token courte durée + refresh token longue durée) |
| Rate limiting login | Haute | Limiter les tentatives sur `POST /api/login` (brute-force) — via `symfony/rate-limiter` |
| HTTPS en production | Haute | Forcer HTTPS et ajouter `Strict-Transport-Security` (HSTS) |
| Validation backend stricte | Moyenne | Ajouter `symfony/validator` sur les DTOs entrants (longueur, format, whitelist) |
| Audit trail complet | Basse | Étendre `LogService` à toutes les mutations sensibles (changement de mot de passe, suppression) |
| CSP affinée | Basse | Affiner la CSP pour autoriser les polices et styles inline MUI sans `unsafe-inline` |
| Rotation des clés JWT | Basse | Documenter la procédure de rotation des clés RSA sans interruption de service |
