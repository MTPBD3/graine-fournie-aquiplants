# Audit PHP2Shell (CVE-2026-109618 / CVE-2026-109619)

Date : 22/10/2026 — audit défensif uniquement (aucun PoC, aucun test contre la prod).
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
- **Image de prod actuelle (`mtpbd3/symfony:latest`, poussée le 07/10/2026)** : construite avant la publication des correctifs (les `php:8.2-fpm` officielles 8.2.35 datent du 21/10/2026). SECURITY_AUDIT_V2 relevait `PHP/8.2.31`. **Vulnérable** (PHP < 8.2.35, SAPI FPM). Version exacte non vérifiée en prod (non testé volontairement) : à confirmer sur le VPS avec `docker exec gf_symfony php -v`.
- **Image reconstruite avec ce hotfix** : PHP 8.2.35 (fpm-fcgi), **non vulnérable**.

### gf_phpmyadmin
- Image `phpmyadmin/phpmyadmin:5.2.1` (construite le 14/01/2025) : PHP bien antérieur à 8.2.35, SAPI Apache mod_php → **vulnérable en version**.
- **Aucune image phpMyAdmin officielle n'embarque un PHP corrigé** : le tag le plus récent (`5.2.3` / `latest`) date du 08/10/2025, donc avant les correctifs. Impossible de corriger par simple montée de tag ; rien n'a été modifié pour ce service.
- **Exposition** : service de dev uniquement, publié sur `127.0.0.1:8080`, absent de `docker-compose.prod.yml`. Risque en prod : nul si le VPS n'a pas lancé ce compose de dev (à vérifier, voir plus bas).

## Actions réalisées
- Branche `hotfix/php2shell-cve-2026-109618` créée depuis `main`.
- `docker/symfony/Dockerfile` : `php:8.2-fpm` → `php:8.2.35-fpm` (tag figé). Aucun autre changement.
- Image reconstruite : `php -v` = 8.2.35, `php-fpm` = 8.2.35, SAPI FPM/FastCGI.
- PHPUnit dans l'image reconstruite : **OK, 242 tests, 370 assertions** (le brief annonçait 241). Tests lancés avec SQLite et clés JWT temporaires, comme la CI.
- `doctrine:schema:validate --skip-sync` : mapping correct ; synchronisation BDD non vérifiée (pas de MySQL dans ce run, avertissement ENUM connu ignoré).
- CI : le job `deploy` reconstruit et pousse `mtpbd3/symfony:latest` à chaque push sur `main` ; le Dockerfile corrigé sera donc publié au merge. Non vérifié de bout en bout (nécessite le merge).

## Reste à faire (manuel)
1. PR `hotfix/php2shell-cve-2026-109618` → `main`, merge si CI verte, puis vérifier sur Docker Hub que `mtpbd3/symfony:latest` est mis à jour, puis back-merge `main` → `develop`.
2. phpMyAdmin : ne pas le lancer en prod ; en dev, l'arrêter tant qu'aucune image corrigée n'est publiée (`docker compose stop phpmyadmin`) ou le retirer.

## Commandes de redéploiement VPS (à lancer manuellement, non exécutées)
```bash
cd <dossier du compose sur le VPS>
docker compose -f docker-compose.prod.yml pull symfony
docker compose -f docker-compose.prod.yml up -d symfony
docker exec gf_symfony php -v          # attendu : PHP 8.2.35
docker exec gf_symfony php-fpm -v
docker compose -f docker-compose.prod.yml ps
```
Sauvegarder la base avant (`docker/scripts/backup.sh`). Ne pas utiliser `down -v`.

## Mesures d'attente / contrôles sur le VPS
```bash
sudo ufw status verbose                 # 22, 80, 443 seulement
sudo ss -tlnp | grep -E ':(3306|3307|8080|8000)\b'   # rien d'exposé sur 0.0.0.0
docker ps --format '{{.Names}} {{.Ports}}'           # gf_phpmyadmin ne doit pas tourner
sudo ufw deny 8080/tcp; sudo ufw deny 3306/tcp       # si besoin
```
Attention : Docker contourne UFW pour les ports publiés ; le contrôle fiable est l'absence de `ports:` publics (cas de `docker-compose.prod.yml`). Exploitation active depuis le 21/10 : consulter les logs nginx/FPM pour des requêtes inhabituelles avant et après la mise à jour.
