# CLAUDE.md — Graine Fournie Aquiplants

Application de gestion des sachets de graines pour la pépinière agricole AQUIPLANTS.
Monorepo : API Symfony 7 (`backend/`) + SPA React 19 (`frontend/`) + Docker Compose à la racine.

---

## Stack technique

| Couche      | Technologie                                      |
|-------------|--------------------------------------------------|
| Backend     | PHP 8.2 / Symfony 7.4 / Doctrine ORM / JWT auth |
| Base de données | MySQL 8.0 (conteneur `gf_mysql`, port 3307)  |
| Frontend    | React 19.2 / MUI v9 / Vite 8 / Recharts 3       |
| Infra       | Docker Compose (4 services)                      |

---

## Services Docker

```
docker compose up -d
```

| Conteneur       | Service         | Port exposé |
|-----------------|-----------------|-------------|
| `gf_mysql`      | MySQL 8.0       | 3307        |
| `gf_symfony`    | API Symfony     | 8000        |
| `gf_react`      | Frontend Vite   | 3000        |
| `gf_phpmyadmin` | phpMyAdmin      | 8080        |

Credentials MySQL : `aquiplants / aquiplants` (db: `aquiplants_db`).
JWT passphrase : `aquiplants`.

**Toujours exécuter les commandes Symfony dans le conteneur :**
```bash
docker exec -it gf_symfony php bin/console <commande>
# Ex : migrations
docker exec -it gf_symfony php bin/console doctrine:migrations:migrate
# Ex : import CSV (utiliser --no-debug pour éviter l'OOM)
docker exec -it gf_symfony php bin/console app:import-csv --no-debug
```

---

## Architecture backend (`backend/`)

### Domaine métier — 11 entités Doctrine

| Entité            | Rôle                                                   |
|-------------------|--------------------------------------------------------|
| `Utilisateur`     | Compte applicatif (email + rôle EMPLOYE ou ADMIN)      |
| `Client`          | Client de la pépinière                                 |
| `Espece`          | Espèce botanique (3 218 lignes importées depuis CSV)   |
| `Plant`           | Plant (variété) lié à une `Espece`                     |
| `Uv`              | Unité de Végétal — liée à une `Espece`                 |
| `GfClient`        | Sachet de graines en stock (entité centrale)           |
| `Emplacement`     | Emplacement physique sur étagère (A-D / étage 1-4)     |
| `CommandeASemer`  | Ordre de semis                                         |
| `GfHistoClient`   | Historique des semis par sachet                        |
| `HistoGfDeposee`  | Historique des dépôts par sachet (statuts : en_attente / en_stock / epuise) |
| `Log`             | Journal d'audit des actions utilisateur               |

### Relation clé Espece / Plant / UV
Chaque `Plant` dans le CSV a son propre `id_espece` unique, même si deux plants partagent
le même nom d'espèce (ex. "CHENE ROUGE" peut avoir les ids 54, 64, 70…).
`EspeceController::uvs()` groupe par **nom d'espèce** pour éviter des dropdowns vides.

### Controllers et routes

| Controller               | Routes principales                                                |
|--------------------------|-------------------------------------------------------------------|
| `AuthController`         | `POST /api/login`, `GET /api/me`                                  |
| `GfClientController`     | CRUD `/api/gf-clients` + actions utiliser/déposer/archiver        |
| `ClientController`       | CRUD `/api/clients`                                               |
| `PlantController`        | CRUD `/api/plants`                                                |
| `EspeceController`       | `GET /api/especes`, `GET /api/especes/{id}/uvs` (groupé par nom)  |
| `UvController`           | CRUD `/api/uvs`                                                   |
| `EmplacementController`  | `GET /api/emplacements/libres|occupes`, assign/libérer            |
| `StatistiquesController` | `GET /api/statistiques`, `GET /api/stats/depots?periode=1M\|3M\|6M` |
| `AlertesController`      | `GET /api/alertes`                                                |
| `HistoGfDeposeeController` | CRUD `/api/histo-gf-deposees`                                   |
| `GfHistoClientController`| CRUD `/api/gf-histo-clients`                                      |
| `UtilisateurController`  | CRUD `/api/utilisateurs` (ROLE_ADMIN)                             |

### Sécurité
- JWT : clés dans `config/jwt/private.pem` et `config/jwt/public.pem` (gitignorées).
- Rôles : `ROLE_ADMIN > ROLE_EMPLOYE`. Routes `/api/*` requièrent `ROLE_EMPLOYE` minimum.
- `POST /api/login` est public.

### Import CSV
Commande `app:import-csv` dans `backend/src/Command/ImportCsvCommand.php`.
Lit les fichiers depuis `backend/var/data/` :
- `R_export_espece.csv` → table `espece`
- `R__Export_Plant.csv` → tables `espece` + `plant`
- `R__Export_UV.csv` → table `uv`

Format CSV : séparateur `;`, encodage ISO-8859-1.
Toujours lancer avec `--no-debug` (sinon OOM à cause du profiler Doctrine).

---

## Architecture frontend (`frontend/`)

### Pages (lazy-loadées)

| Page                      | Route                    | Rôle                                  |
|---------------------------|--------------------------|---------------------------------------|
| `LoginPage`               | `/`                      | Authentification JWT                  |
| `DashboardAdminPage`      | `/dashboard/admin`       | KPIs, graphique évolution dépôts      |
| `DashboardEmployePage`    | `/dashboard/employe`     | Vue employé                           |
| `ArriveesSachetsPage`     | `/arrivees-sachets`      | Saisie et gestion des sachets entrants|
| `GestionStocksPage`       | `/gestion-stocks`        | Grille d'emplacements A1–D4           |
| `StatistiquesPage`        | `/statistiques`          | Graphiques statistiques               |
| `AlertesPage`             | `/alertes`               | Alertes stock (ROLE_ADMIN)            |
| `GestionUtilisateursPage` | `/gestion-utilisateurs`  | CRUD utilisateurs (ROLE_ADMIN)        |
| `ParametresPage`          | `/parametres`            | Paramètres                            |

### Hooks et utilitaires

- `useApi(path, { skip })` — fetch JWT, **cache 30 s** en mémoire par path.
  `refetch()` invalide le cache et force un nouveau fetch (à appeler après mutations).
  `invalidateCache(path?)` disponible pour invalidation manuelle.
- `apiRequest(path, method, body, token)` — mutation HTTP (POST/PUT/DELETE).
- `AuthContext` — token JWT + user stockés dans `localStorage`.

### Composants notables

- `Layout` — sidebar responsive + header mobile.
- `EvolutionDepotsChart` — graphique Recharts avec filtre 1M/3M/6M, connecté à `/api/stats/depots`.

### MUI v9 — points d'attention
- Utiliser `slotProps.htmlInput` (pas `inputProps`) et `slotProps.input` (pas `InputProps`).
- Dans `Autocomplete.renderInput`, destructurer `{ inputProps, InputProps, ...params }` et
  passer `slotProps={{ htmlInput: inputProps, input: InputProps }}` à `TextField`.
- Imports d'icônes : toujours par chemin direct (`@mui/icons-material/NomIcone`), jamais barrel.

### Vite — chunks en production
MUI, icônes, Recharts et React sont dans des chunks séparés (`vendor-mui`, `vendor-mui-icons`,
`vendor-recharts`, `vendor-react`) pour optimiser le cache navigateur.

---

## Workflow de développement

```bash
# Démarrer tout
docker compose up -d

# Logs d'un service
docker compose logs -f symfony_app

# Migration après changement d'entité
docker exec -it gf_symfony php bin/console make:migration
docker exec -it gf_symfony php bin/console doctrine:migrations:migrate

# Vider le cache Symfony
docker exec -it gf_symfony php bin/console cache:clear

# Déboguer les routes
docker exec -it gf_symfony php bin/console debug:router

# Import des données de référence
docker exec -it gf_symfony php bin/console app:import-csv --no-debug
```

Accès en développement local (hors Docker) :
- Créer `frontend/.env.development.local` avec `VITE_API_URL=http://192.168.0.133:8000` (IP LAN).
- En mode Docker, `VITE_API_URL=""` → proxy Vite `/api` → `symfony_app:8000`.
