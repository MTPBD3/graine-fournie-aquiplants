#!/bin/bash
# Script d'initialisation des utilisateurs MySQL — exécuté automatiquement
# au premier démarrage du conteneur MySQL (répertoire de données vide).

set -e

mysql -u root -p"${MYSQL_ROOT_PASSWORD}" <<EOF
-- Restreindre l'utilisateur applicatif au DML uniquement
REVOKE ALL PRIVILEGES ON \`${MYSQL_DATABASE}\`.* FROM '${MYSQL_USER}'@'%';
GRANT SELECT, INSERT, UPDATE, DELETE ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_USER}'@'%';

-- Créer l'utilisateur de migrations (DDL autorisé)
CREATE USER IF NOT EXISTS '${MYSQL_MIGRATIONS_USER}'@'%' IDENTIFIED BY '${MYSQL_MIGRATIONS_PASSWORD}';
GRANT SELECT, INSERT, UPDATE, DELETE, CREATE, ALTER, DROP, INDEX, REFERENCES
    ON \`${MYSQL_DATABASE}\`.* TO '${MYSQL_MIGRATIONS_USER}'@'%';

FLUSH PRIVILEGES;
EOF
