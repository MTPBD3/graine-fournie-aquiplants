# Déploiement production

## Infrastructure

- VPS OVH : `46.105.29.41` (Ubuntu 24.04, Strasbourg)
- Domaine : `graine-fournie.online` (+ `www.graine-fournie.online`, redirigé vers le domaine nu), acheté et DNS géré chez OVH
- URL de production : **https://graine-fournie.online**
- Dossier de déploiement sur le serveur : `/opt/graine-fournie-prod/`
- Stack : `docker-compose.prod.yml` (5 services : `mysql`, `symfony`, `react`, `nginx`, `certbot`)
- Images Docker Hub : `mtpbd3/symfony:latest`, `mtpbd3/react:latest`
- nginx fait le reverse proxy : `/api` → `symfony:8000`, `/` → `react:80`, et termine le TLS (HTTPS)

## Fichiers de référence (source de vérité)

- `docker-compose.prod.yml` (racine du repo)
- `nginx.conf` (racine du repo)
- `.env.prod.example` → à copier en `.env.prod` sur le serveur (non commité, contient les vrais secrets)

Pour déployer ces fichiers sur le serveur :
```bash
scp docker-compose.prod.yml nginx.conf ubuntu@46.105.29.41:/opt/graine-fournie-prod/
# puis sur le serveur, remplir /opt/graine-fournie-prod/.env.prod à partir de .env.prod.example
```

## Déployer une nouvelle version d'une image

```bash
# build + push depuis la machine de dev
docker build -f docker/symfony/Dockerfile -t mtpbd3/symfony:latest .
docker push mtpbd3/symfony:latest

# sur le VPS : tirer et redémarrer uniquement le service concerné
cd /opt/graine-fournie-prod
docker compose -f docker-compose.prod.yml --env-file .env.prod pull symfony
docker compose -f docker-compose.prod.yml --env-file .env.prod up -d symfony
```

Idem pour `react` en remplaçant `symfony` par `react`.

## Historique des fixes

### 1. Frontend "Failed to fetch" — VITE_API_URL figé sur localhost

**Symptôme** : le navigateur affichait "Failed to fetch" à la connexion sur `http://46.105.29.41`.

**Cause** : `docker/react/Dockerfile` ne fait pas de build Vite — le conteneur lance directement `npm run dev` (serveur de dev Vite), en local comme en prod, avec la même image. `import.meta.env.VITE_API_URL` est donc lu par le serveur Vite **au démarrage du conteneur**, jamais compilé en dur dans l'image. `docker-compose.prod.yml` ne définissait pas cette variable pour le service `react`, qui retombait sur la valeur de `frontend/.env` copiée dans l'image (`http://localhost:8000`) — injoignable depuis le navigateur du visiteur.

**Fix** : ajouter `VITE_API_URL: ""` au service `react` dans `docker-compose.prod.yml`, comme c'est déjà fait dans le `docker-compose.yml` local. Avec une valeur vide, le front appelle des chemins relatifs (`/api/...`), que nginx route vers `symfony:8000` — exactement le même principe que le proxy Vite utilisé en dev local (`frontend/vite.config.js`, bloc `server.proxy`).

**⚠️ Important** : ce fix vit entièrement dans `docker-compose.prod.yml`, **pas** dans l'image `mtpbd3/react:latest` sur Docker Hub — l'image elle-même n'a pas été rebuild ni repoussée, et n'a pas besoin de l'être pour ce fix. Si `docker-compose.prod.yml` est un jour perdu, réinitialisé, ou que quelqu'un relance le service `react` sans ce fichier (ou une version qui a perdu le bloc `environment:`), le bug reviendra immédiatement, même avec l'image Docker Hub à jour. **Ce fichier versionné (`docker-compose.prod.yml` à la racine du repo) est donc la seule source de vérité pour ce comportement** — ne pas le recréer à la main sur le serveur sans repartir de cette version.

Pas de rebuild/push nécessaire pour ce fix : juste éditer `docker-compose.prod.yml` sur le serveur et relancer `docker compose -f docker-compose.prod.yml up -d react`.

### 2. API inaccessible (502) — hostname MySQL en dur dans init.sh

**Symptôme** : après le fix #1, `/api/*` renvoyait systématiquement 502. `gf_symfony` restait bloqué en boucle sur "MySQL pas encore prêt" indéfiniment alors que MySQL tournait normalement.

**Cause** : `docker/scripts/init.sh` (intégré dans l'image `mtpbd3/symfony:latest`) testait la connexion MySQL avec un hostname et des identifiants en dur (`mysql_db` / `aquiplants` / `aquiplants_db`), hérités du compose de dev (`docker-compose.yml`, service `mysql_db`). En prod, le service s'appelle `mysql` avec d'autres identifiants (`docker-compose.prod.yml`) : la connexion échouait en boucle, sans jamais atteindre le timeout (pas de limite dans la boucle `until`).

**Fix** : `init.sh` parse désormais `DATABASE_URL` (`parse_url()` en PHP) au lieu de valeurs en dur, cette variable étant déjà correctement injectée aussi bien en dev qu'en prod. Nécessite un rebuild + push de l'image `mtpbd3/symfony:latest`.

### 3. API toujours en 502 après le fix #2 — crash sur les fixtures en prod

**Symptôme** : toujours 502 après le fix #2, mais cette fois `gf_symfony` plantait net à l'étape "Fixtures" au lieu de boucler.

**Cause** : `init.sh` a `set -e`. L'étape fixtures appelait `doctrine:fixtures:load`, une commande fournie par `DoctrineFixturesBundle`, qui n'est enregistré que pour les environnements `dev`/`test` (voir `config/bundles.php`) — en prod la commande n'existe pas, la commande échoue, et `set -e` tue tout le script avant même de démarrer PHP-FPM/nginx.

**Fix** : `init.sh` saute désormais entièrement l'étape fixtures quand `APP_ENV=prod`. Nécessite un rebuild + push de l'image `mtpbd3/symfony:latest`.

### 4. JWT_PASSPHRASE / APP_SECRET absents en prod + rotation de secrets exposés

**Constat** : `docker exec gf_symfony printenv` ne montrait ni `JWT_PASSPHRASE` ni `APP_SECRET` — Symfony utilisait donc `APP_SECRET=changeme_in_ci` et `JWT_PASSPHRASE=changeme_in_ci` (valeurs par défaut de `backend/.env`), des secrets faibles et prévisibles. Par ailleurs, un mot de passe MySQL et deux tokens Docker Hub avaient transité en clair dans une session de debug (tous rotatés/révoqués depuis, valeurs non reproduites ici).

**Fix** :
- `APP_SECRET` et `JWT_PASSPHRASE` ajoutés à `docker-compose.prod.yml` (service `symfony`) et générés avec `openssl rand -hex 32` / `openssl rand -hex 24`.
- Mot de passe `root` et mot de passe de l'utilisateur applicatif (`gf_user`) MySQL changés en base via `ALTER USER` (le volume `mysql_data` étant déjà initialisé, changer `MYSQL_ROOT_PASSWORD`/`MYSQL_PASSWORD` dans `.env.prod` seul n'aurait eu aucun effet — ces variables ne sont lues par l'image MySQL qu'à la création initiale du volume).
- `.env.prod` régénéré sur le VPS avec les nouvelles valeurs (fichier non commité, cf. `.env.prod.example` pour le gabarit).
- Les deux tokens Docker Hub exposés (`dckr_pat_...`) doivent être révoqués manuellement sur hub.docker.com (Account Settings → Security → Personal access tokens) — action hors de portée des outils disponibles pour cette session.
- Vérifié de bout en bout après rotation : connexion MySQL OK, génération des clés JWT OK, `POST /api/login` renvoie un vrai token JWT signé avec la nouvelle passphrase, `GET /api/me` avec ce token renvoie 200. Testé avec un utilisateur temporaire créé puis supprimé après vérification (aucune donnée de test laissée en base).

### 5. Passage en HTTPS (2026-10-07)

**Pourquoi** : l'app tournait en HTTP simple sur l'IP du VPS — tous les mots de passe et tokens de connexion circulaient en clair sur le réseau, interceptables par quiconque se trouve entre le navigateur et le serveur (wifi public, FAI...). Un nom de domaine (`graine-fournie.online`, acheté chez OVH) a été pointé vers le VPS pour pouvoir obtenir un certificat TLS gratuit.

#### Comment fonctionne un certificat Let's Encrypt

Un certificat TLS sert à deux choses : chiffrer le trafic, et prouver au navigateur qu'il parle bien au vrai `graine-fournie.online` (pas à un attaquant qui se ferait passer pour lui). Let's Encrypt le délivre gratuitement via une **validation de domaine automatisée** (protocole ACME) :

1. On demande un certificat pour `graine-fournie.online` à Let's Encrypt.
2. Let's Encrypt renvoie un "challenge" : un fichier à déposer à une URL précise, `http://graine-fournie.online/.well-known/acme-challenge/<jeton>`.
3. `certbot` (le logiciel client) dépose ce fichier dans un dossier partagé avec nginx (`certbot/www/`).
4. Let's Encrypt va chercher cette URL lui-même, depuis internet. S'il trouve le bon contenu, ça prouve qu'on contrôle bien le serveur derrière ce nom de domaine (**méthode "webroot"**).
5. Le certificat est alors délivré, valable **90 jours** — volontairement court, pour forcer un renouvellement automatisé régulier plutôt que des certificats oubliés et expirés pendant des années.

Ce mécanisme de preuve est la raison pour laquelle on vérifie d'abord le DNS (`nslookup`) avant de rien demander : si le domaine ne pointe pas encore vers le bon serveur, Let's Encrypt ne pourra jamais valider le challenge, et chaque tentative ratée compte dans un quota limité (d'où l'usage de `--dry-run` d'abord, qui simule tout sans consommer ce quota).

#### Fichiers modifiés et pourquoi

- **`nginx.conf`** (racine du repo) : réécrit avec 3 blocs `server` —
  1. un sur le port 80 qui sert uniquement `/.well-known/acme-challenge/` (nécessaire pour le renouvellement, qui repasse toujours par cette vérification) et redirige tout le reste en `301` vers `https://graine-fournie.online` ;
  2. un dédié à `www.graine-fournie.online` (en HTTP et HTTPS) qui redirige systématiquement vers le domaine sans `www` ;
  3. un sur le port 443 (`ssl`, `http2`) qui sert réellement l'application, avec le certificat Let's Encrypt et les en-têtes `X-Forwarded-*` transmis à Symfony et React.
- **`docker-compose.prod.yml`** : le service `nginx` expose désormais aussi le port `443`, monte deux volumes (`certbot/conf` → certificats, `certbot/www` → dossier du challenge). Un nouveau service `certbot` est ajouté, mais **jamais démarré en continu** (pas de `restart:`) — il n'est lancé qu'à la demande, pour obtenir ou renouveler un certificat.
- **`backend/config/packages/framework.yaml`** : ajout de `trusted_proxies` et `trusted_headers` (détail ci-dessous).
- **`DEPLOYMENT.md`** (ce fichier) : cette section.

#### Pourquoi `X-Forwarded-Proto` et `trusted_proxies` sont nécessaires

Dans cette architecture, **nginx est le seul service exposé à internet** ; Symfony et React ne reçoivent jamais directement les requêtes des visiteurs, seulement celles de nginx, sur le réseau Docker interne (`gf_net`). Problème : quand nginx relaie une requête HTTPS vers Symfony en interne, cette connexion interne nginx → Symfony est elle-même en HTTP simple (pas besoin de chiffrer sur un réseau privé non routable depuis internet). **Du point de vue de Symfony, la requête a donc toujours l'air d'arriver en HTTP**, même quand le visiteur est bien en HTTPS.

nginx compense en ajoutant un en-tête `X-Forwarded-Proto: https` à chaque requête qu'il relaie, pour dire à Symfony "l'original était en HTTPS, fais comme si". Mais Symfony n'a par défaut aucune raison de croire cet en-tête : **n'importe qui pourrait l'envoyer lui-même** pour mentir sur l'origine de sa requête. `trusted_proxies` dit donc à Symfony : "si la requête vient de telle plage d'IP (ici, les plages privées du réseau Docker, non joignables depuis internet), alors fais confiance aux en-têtes `X-Forwarded-*` qu'elle contient". Sans ça : Symfony croirait être en HTTP, ce qui peut casser des redirections, des cookies marqués "secure", ou des URLs absolues générées par l'application.

Ici, l'app est une API JWT stateless (pas de session/cookie de connexion), donc l'impact direct était limité — mais c'est la configuration correcte et attendue derrière tout reverse proxy, et une mauvaise config à cet endroit est une source classique de bugs difficiles à diagnostiquer ("pourquoi ça boucle en redirection seulement en prod ?").

#### Commandes utiles

```bash
# Depuis la machine de dev : vérifier le DNS avant toute demande de certificat
nslookup graine-fournie.online
nslookup www.graine-fournie.online
# Les deux doivent renvoyer 46.105.29.41

# Sur le VPS : demander un certificat (d'abord en dry-run)
cd /opt/graine-fournie-prod
docker compose -f docker-compose.prod.yml run --rm certbot certonly \
  --webroot --webroot-path=/var/www/certbot \
  --email <email> --agree-tos --no-eff-email \
  -d graine-fournie.online -d www.graine-fournie.online \
  --dry-run        # retirer cette ligne une fois le dry-run validé

# Recharger nginx après avoir changé nginx.conf (sans redémarrer le conteneur)
docker exec gf_nginx nginx -t        # vérifie la syntaxe avant de recharger
docker exec gf_nginx nginx -s reload

# Tester le renouvellement sans consommer de quota
docker compose -f docker-compose.prod.yml --env-file .env.prod run --rm certbot renew --dry-run
```

#### Renouvellement automatique

Un certificat Let's Encrypt dure 90 jours. `/opt/graine-fournie-prod/renew-cert.sh` (sur le serveur, non versionné) lance `certbot renew` (qui ne renouvelle réellement que si le certificat expire dans moins de 30 jours — sinon il ne fait rien) puis recharge nginx. Une tâche **cron** l'exécute deux fois par jour :

```
17 3,15 * * * /opt/graine-fournie-prod/renew-cert.sh >> /var/log/certbot-renew.log 2>&1
```

#### Vérifications effectuées

| Test | Résultat |
|---|---|
| `curl -I https://graine-fournie.online` | `200 OK` |
| `curl -I http://graine-fournie.online` | `301` vers `https://graine-fournie.online` |
| `curl -I https://www.graine-fournie.online` | redirige vers `https://graine-fournie.online` |
| `curl -I http://46.105.29.41` | `301` vers `https://graine-fournie.online` |
| Login réel dans l'app (compte admin) | OK, token JWT valide reçu et utilisable |
| `GET /api/utilisateurs` sans token | `401` (normal, route protégée) |
| Expiration du certificat | `2027-01-05` (délivré le 2026-10-07) |
| `certbot renew --dry-run` | succès |

**Note sur `Strict-Transport-Security`** : volontairement réglé à `max-age=300` (5 minutes) pour l'instant, le temps de confirmer que tout fonctionne durablement sans accroc. Une fois validé sur quelques jours, augmenter cette valeur (ex. `max-age=31536000` = 1 an) pour que les navigateurs refusent définitivement de repasser en HTTP sur ce domaine.
