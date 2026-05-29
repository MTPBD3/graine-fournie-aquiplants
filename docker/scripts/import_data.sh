#!/bin/bash
set -e

CONTAINER="gf_mysql"
DB="aquiplants_db"
SECURE_DIR="/var/lib/mysql-files"
SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
DATA_DIR="$(cd "$SCRIPT_DIR/../data" && pwd)"

CSV_FILES=(
    "R_export_espece.csv"
    "R__Export_Plant.csv"
    "R__Export_UV.csv"
    "R__Export_Client.csv"
)

echo "=== Import CSV → MySQL ($DB) ==="

echo "--- [1/3] Vérification des fichiers CSV dans $DATA_DIR ---"
for f in "${CSV_FILES[@]}"; do
    if [ ! -f "$DATA_DIR/$f" ]; then
        echo "  ERREUR : $f introuvable dans $DATA_DIR" >&2
        exit 1
    fi
    echo "  OK : $f"
done

echo "--- [2/3] Copie dans le conteneur ($CONTAINER:$SECURE_DIR) ---"
for f in "${CSV_FILES[@]}"; do
    docker cp "$DATA_DIR/$f" "$CONTAINER:$SECURE_DIR/$f"
    echo "  $f"
done

# Import via root (privilège FILE requis pour LOAD DATA INFILE)
# MYSQL_PWD lit le mot de passe depuis l'env du conteneur, sans l'exposer en CLI
# Ordre FK : espece → client → plant → uv
echo "--- [3/3] Import LOAD DATA INFILE ---"
docker exec -i "$CONTAINER" \
    sh -c 'MYSQL_PWD=$MYSQL_ROOT_PASSWORD mysql -uroot '"$DB" \
    << 'ENDSQL'
SET FOREIGN_KEY_CHECKS = 0;

-- 1/4 espece
LOAD DATA INFILE '/var/lib/mysql-files/R_export_espece.csv'
REPLACE INTO TABLE espece
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_espece, nom_espece);
SELECT CONCAT('  espece  : ', ROW_COUNT(), ' lignes traitées') AS '';

-- 2/4 client (prenom_client absent du CSV → NULL)
LOAD DATA INFILE '/var/lib/mysql-files/R__Export_Client.csv'
REPLACE INTO TABLE client
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_client, nom_client);
SELECT CONCAT('  client  : ', ROW_COUNT(), ' lignes traitées') AS '';

-- 3/4 plant
LOAD DATA INFILE '/var/lib/mysql-files/R__Export_Plant.csv'
REPLACE INTO TABLE plant
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_plant, nom_plant, id_espece);
SELECT CONCAT('  plant   : ', ROW_COUNT(), ' lignes traitées') AS '';

-- 4/4 uv (CSV : id_uv;id_espece;nom_uv;nombrePlantParPlateaux;nombreGraineParMotte)
LOAD DATA INFILE '/var/lib/mysql-files/R__Export_UV.csv'
REPLACE INTO TABLE uv
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_uv, id_espece, nom_uv, nombre_plant_par_plateaux, nombre_graine_par_motte);
SELECT CONCAT('  uv      : ', ROW_COUNT(), ' lignes traitées') AS '';

SET FOREIGN_KEY_CHECKS = 1;
ENDSQL

echo ""
echo "=== Import terminé — lancement du backup ==="
bash "$SCRIPT_DIR/backup.sh"
