#!/bin/bash
set -e

OUTPUT="backup_$(date +%Y%m%d_%H%M%S).sql"
docker exec gf_mysql mysqldump -uaquiplants -paquiplants aquiplants_db > "$OUTPUT"
echo "Dump créé : $OUTPUT"
