$ErrorActionPreference = 'Stop'

$CONTAINER  = "gf_mysql"
$DB         = "aquiplants_db"
$SECURE_DIR = "/var/lib/mysql-files"
$DATA_DIR   = Resolve-Path (Join-Path $PSScriptRoot "..\data")

$csvFiles = @(
    "R_export_espece.csv",
    "R__Export_Plant.csv",
    "R__Export_UV.csv",
    "R__Export_Client.csv"
)

Write-Host "=== Import CSV -> MySQL ($DB) ==="

# 1. Verification
Write-Host "--- [1/3] Verification des fichiers CSV dans $DATA_DIR ---"
foreach ($f in $csvFiles) {
    $fullPath = Join-Path $DATA_DIR $f
    if (-not (Test-Path $fullPath)) {
        Write-Error "ERREUR : $f introuvable dans $DATA_DIR"
        exit 1
    }
    Write-Host "  OK : $f"
}

# 2. docker cp
Write-Host "--- [2/3] Copie dans le conteneur (${CONTAINER}:${SECURE_DIR}) ---"
foreach ($f in $csvFiles) {
    $fullPath = Join-Path $DATA_DIR $f
    docker cp $fullPath "${CONTAINER}:${SECURE_DIR}/$f"
    if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }
    Write-Host "  $f"
}

# 3. LOAD DATA INFILE
# Ordre FK : espece -> client -> plant -> uv
# \n = MySQL escape sequence (LF), coherent avec eol=lf dans .gitattributes
Write-Host "--- [3/3] Import LOAD DATA INFILE ---"
$sql = @"
SET FOREIGN_KEY_CHECKS = 0;

LOAD DATA INFILE '/var/lib/mysql-files/R_export_espece.csv'
REPLACE INTO TABLE espece
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_espece, nom_espece);
SELECT CONCAT('  espece  : ', ROW_COUNT(), ' lignes traitees') AS '';

LOAD DATA INFILE '/var/lib/mysql-files/R__Export_Client.csv'
REPLACE INTO TABLE client
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_client, nom_client);
SELECT CONCAT('  client  : ', ROW_COUNT(), ' lignes traitees') AS '';

LOAD DATA INFILE '/var/lib/mysql-files/R__Export_Plant.csv'
REPLACE INTO TABLE plant
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_plant, nom_plant, id_espece);
SELECT CONCAT('  plant   : ', ROW_COUNT(), ' lignes traitees') AS '';

LOAD DATA INFILE '/var/lib/mysql-files/R__Export_UV.csv'
REPLACE INTO TABLE uv
CHARACTER SET latin1
FIELDS TERMINATED BY ';' ENCLOSED BY '"'
LINES TERMINATED BY '\n'
IGNORE 1 ROWS
(id_uv, id_espece, nom_uv, nombre_plant_par_plateaux, nombre_graine_par_motte);
SELECT CONCAT('  uv      : ', ROW_COUNT(), ' lignes traitees') AS '';

SET FOREIGN_KEY_CHECKS = 1;
"@

$sql | docker exec -i $CONTAINER mysql -uroot -proot $DB
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

# Backup automatique
Write-Host ""
Write-Host "=== Import termine - lancement du backup ==="
$OUTPUT = "backup_$(Get-Date -Format 'yyyyMMdd_HHmmss').sql"
docker exec $CONTAINER mysqldump -uaquiplants -paquiplants $DB | Out-File -FilePath $OUTPUT -Encoding utf8
Write-Host "Dump cree : $OUTPUT"

Write-Host ""
Write-Host "=== Termine ==="
