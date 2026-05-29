#!/bin/bash
# Script d'initialisation des utilisateurs MySQL — exécuté automatiquement
# au premier démarrage du conteneur MySQL (répertoire de données vide).

set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<EOF
-- Utilisateur applicatif (DML uniquement)
CREATE USER IF NOT EXISTS '${MYSQL_USER}'@'%' IDENTIFIED BY '${MYSQL_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';

-- Utilisateur de migrations (DDL autorisé)
CREATE USER IF NOT EXISTS '${MYSQL_MIGRATIONS_USER}'@'%' IDENTIFIED BY '${MYSQL_MIGRATIONS_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
    ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_MIGRATIONS_USER}'@'%';

FLUSH PRIVILEGES;
EOF
