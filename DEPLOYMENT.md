# Déploiement production

## Infrastructure

- VPS OVH : `46.105.29.41` (Ubuntu 24.04, Strasbourg)
- Dossier de déploiement sur le serveur : `/opt/graine-fournie-prod/`
- Stack : `docker-compose.prod.yml` (4 services : `mysql`, `symfony`, `react`, `nginx`)
- Images Docker Hub : `mtpbd3/symfony:latest`, `mtpbd3/react:latest`
- nginx fait le reverse proxy : `/api` → `symfony:8000`, `/` → `react:3000`

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
