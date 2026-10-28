# Audit PHP2Shell (CVE-2026-109618 / CVE-2026-109619)

Date : 22/10/2026, mis à jour le 28/10/2026 — audit défensif uniquement (aucun PoC, aucun test contre la prod).
Versions corrigées : PHP 8.2.35, 8.3.36, 8.4.27, 8.5.12. SAPI concernés : PHP-FPM, Apache mod_php, php-cgi, FrankenPHP, LiteSpeed.

## Versions et SAPI trouvés

| Élément | Constat |
|---|---|
| `docker/symfony/Dockerfile` (avant) | `FROM php:8.2-fpm` — tag **flottant** (suit la dernière 8.2.x) |
| `docker-compose.yml` | `symfony_app` construit depuis ce Dockerfile ; `phpmyadmin/phpmyadmin:5.2.1` |
| `docker-compose.prod.yml` | `mtpbd3/symfony:latest` ; **pas de service phpMyAdmin** ; MySQL et Symfony sans port publié (seul nginx publie 80/443) |
| `backend/composer.json` | `"php": ">=8.2"`, pas de `config.platform.php` |
| `.github/workflows/ci.yml` | tests avec `shivammathur/setup-php` `8.2` (flottant, usage test uniquement) ; job `deploy` pousse `<user>/symfony:latest` et `<user>/react:latest` uniquement sur push `main` |
| SAPI `gf_symfony` | **PHP-FPM** (`php-fpm --nodaemonize` via supervisord, derrière nginx dans le conteneur) → SAPI concerné |
| SAPI `gf_phpmyadmin` | image `phpmyadmin/phpmyadmin` standard (variante Apache) → **Apache mod_php**, SAPI concerné |

Le démon Docker n'était pas lancé au départ ; il a été démarré pour reconstruire et tester. Les conteneurs de prod n'ont pas été interrogés.

## Verdict par conteneur

### gf_symfony
- **Image de prod actuelle (`mtpbd3/symfony:latest`, poussée le 07/10/2026)** : construite avant la publication des correctifs (les `php:8.2-fpm` officielles 8.2.35 datent du 21/10/2026). SECURITY_AUDIT_V2 relevait `PHP/8.2.31`. **Vulnérable** (PHP < 8.2.35, SAPI FPM). Version exacte confirmée le 28/10 en inspectant localement l'image Docker Hub `mtpbd3/symfony:latest` (celle que le VPS a tirée le 07/10, sans connexion à la prod) : **PHP 8.2.34**, `php-fpm` 8.2.34 (fpm-fcgi).
- **Image reconstruite avec ce hotfix** : PHP 8.2.35 (fpm-fcgi), **non vulnérable**.

### gf_phpmyadmin
- Image `phpmyadmin/phpmyadmin:5.2.1` (construite le 14/01/2025) : **PHP 8.2.27** (mesuré le 28/10), SAPI Apache mod_php → **vulnérable**.
- Au 22/10, aucune image phpMyAdmin officielle n'embarquait de PHP corrigé. **Le 27/10/2026, le tag `5.2.3` a été republié avec PHP 8.3.36** (corrigé, mesuré le 28/10) : `docker-compose.yml` passe donc de `5.2.1` à `5.2.3`.
- **Exposition** : service de dev uniquement, publié sur `127.0.0.1:8080`, absent de `docker-compose.prod.yml`. Risque en prod : nul si le VPS n'a pas lancé ce compose de dev (à vérifier, voir plus bas).

## Actions réalisées
- Branche `hotfix/php2shell-cve-2026-109618` créée depuis `main`.
- `docker/symfony/Dockerfile` : `php:8.2-fpm` → `php:8.2.35-fpm` (tag figé). Aucun autre changement.
- Image reconstruite : `php -v` = 8.2.35, `php-fpm` = 8.2.35, SAPI FPM/FastCGI.
- PHPUnit dans l'image reconstruite : **OK, 242 tests, 370 assertions** (le brief annonçait 241). Tests lancés avec SQLite et clés JWT temporaires, comme la CI.
- `doctrine:schema:validate --skip-sync` : mapping correct ; synchronisation BDD non vérifiée (pas de MySQL dans ce run, avertissement ENUM connu ignoré).
- CI : le job `deploy` reconstruit et pousse `mtpbd3/symfony:latest` à chaque push sur `main` ; le Dockerfile corrigé sera donc publié au merge. Non vérifié de bout en bout (nécessite le merge).
- 28/10 : `docker-compose.yml` → `phpmyadmin/phpmyadmin:5.2.3` (PHP 8.3.36). Tests rejoués sur l'image reconstruite : Unit 36/36, Functional 206/206. `php:8.2.35-fpm` et `php:8.2.35-fpm-trixie` ont le même digest (`sha256:7f97976c…`).
- 28/10 : **le job `deploy` échoue depuis le 14/10** (`unauthorized: incorrect username or password`) : le secret GitHub `DOCKERHUB_TOKEN` date du 20/05/2026 et correspond à un token révoqué. Sans renouvellement, le merge ne publie pas l'image.

## Reste à faire (manuel)
1. Renouveler le secret : token *Read & Write* sur hub.docker.com, puis GitHub → Settings → Secrets and variables → Actions → `DOCKERHUB_TOKEN` → Update.
2. PR `hotfix/php2shell-cve-2026-109618` → `main`, merge si CI verte, puis vérifier sur Docker Hub que `mtpbd3/symfony:latest` est mis à jour, puis back-merge `main` → `develop`.
3. phpMyAdmin : ne jamais le lancer en prod ; en dev, relancer le conteneur pour passer en 5.2.3 (`docker compose up -d phpmyadmin`).

## Commandes de redéploiement VPS (à lancer manuellement, non exécutées)
```bash
ssh ubuntu@46.105.29.41
cd /opt/graine-fournie-prod

# 1. Sauvegarde de la base (docker/scripts/backup.sh utilise les identifiants de dev : inutilisable en prod)
sudo mkdir -p backups
sudo sh -c '. ./.env.prod; docker exec -e MYSQL_PWD="$MYSQL_ROOT_PASSWORD" gf_mysql \
  mysqldump -uroot --single-transaction "$MYSQL_DATABASE" > backups/avant-php2shell-$(date +%F).sql'
sudo chmod 600 backups/*.sql

# 2. Mise à jour (--env-file obligatoire : sans lui, APP_SECRET, JWT_PASSPHRASE et DATABASE_URL seraient vides)
sudo docker compose -f docker-compose.prod.yml --env-file .env.prod pull symfony react
sudo docker compose -f docker-compose.prod.yml --env-file .env.prod up -d symfony react

# 3. Vérifications
sudo docker exec gf_symfony php -v          # attendu : PHP 8.2.35
sudo docker exec gf_symfony php-fpm -v      # attendu : 8.2.35 (fpm-fcgi)
sudo docker compose -f docker-compose.prod.yml --env-file .env.prod ps
curl -sI https://graine-fournie.online/ | head -1
```
Ne pas utiliser `down -v`. Le redémarrage de `gf_symfony` régénère les clés JWT : tous les utilisateurs devront se reconnecter une fois. L'image `react` embarque aussi les corrections du 14/10 (jeton 4 h, double encodage HTML).

## Mesures d'attente / contrôles sur le VPS
```bash
sudo ufw status verbose                 # 22, 80, 443 seulement
sudo ss -tlnp | grep -E ':(3306|3307|8080|8000)\b'   # rien d'exposé sur 0.0.0.0
docker ps --format '{{.Names}} {{.Ports}}'           # gf_phpmyadmin ne doit pas tourner
sudo ufw deny 8080/tcp; sudo ufw deny 3306/tcp       # si besoin
```
Attention : Docker contourne UFW pour les ports publiés ; le contrôle fiable est l'absence de `ports:` publics (cas de `docker-compose.prod.yml`). Exploitation active depuis le 21/10 : consulter les logs nginx/FPM pour des requêtes inhabituelles avant et après la mise à jour.
