#!/usr/bin/env bash
# =============================================================================
#  Se ejecuta EN EL SERVIDOR (Hostinger) antes de subir una versión nueva.
#  Lo llama el job "Desplegar en Hostinger" de .github/workflows/ci-cd.yml:
#     ssh servidor 'bash -s' -- <ruta-del-proyecto> <versión> < scripts/deploy/antes.sh
#
#  1. Respalda el código actual y la base de datos en ~/respaldos (quedan las 5 más recientes).
#  2. Pone el sitio en modo mantenimiento mientras se suben los archivos.
# =============================================================================
set -euo pipefail

APP="$1"
VERSION="$2"
RESPALDOS="$HOME/respaldos"
STAMP="$(date +%Y%m%d-%H%M%S)"

cd "$APP"
if [ ! -f artisan ]; then
  echo "::error::No se encontró artisan en $APP. Revisa el secreto HOSTINGER_APP_PATH."
  exit 1
fi
mkdir -p "$RESPALDOS"

echo "Respaldando el código actual (antes de v$VERSION)…"
tar -czf "$RESPALDOS/codigo-$STAMP.tgz" \
  --exclude=./vendor --exclude=./node_modules --exclude=./storage --exclude=./public/avatars .

# Lee un valor del .env sin cargarlo completo en el entorno.
valor_env() {
  grep -E "^$1=" .env | tail -n 1 | cut -d= -f2- | sed -e 's/^"//' -e 's/"$//' -e "s/^'//" -e "s/'$//"
}

if command -v mysqldump > /dev/null && [ "$(valor_env DB_CONNECTION)" = "mysql" ]; then
  echo "Respaldando la base de datos…"
  # La contraseña va por variable de entorno, no en la línea de comandos (otros usuarios del servidor no la ven).
  if MYSQL_PWD="$(valor_env DB_PASSWORD)" mysqldump --single-transaction --no-tablespaces \
      -h "$(valor_env DB_HOST)" -P "$(valor_env DB_PORT)" -u "$(valor_env DB_USERNAME)" "$(valor_env DB_DATABASE)" \
      | gzip > "$RESPALDOS/bd-$STAMP.sql.gz"; then
    echo "Respaldo de BD: $RESPALDOS/bd-$STAMP.sql.gz"
  else
    rm -f "$RESPALDOS/bd-$STAMP.sql.gz"
    echo "::warning::No se pudo respaldar la base de datos; usa los respaldos de hPanel si necesitas revertir."
  fi
else
  echo "::warning::mysqldump no está disponible; se omite el respaldo de la base de datos."
fi

# Solo se guardan los 5 respaldos más recientes de cada tipo.
ls -1t "$RESPALDOS"/codigo-*.tgz 2> /dev/null | tail -n +6 | xargs -r rm --
ls -1t "$RESPALDOS"/bd-*.sql.gz 2> /dev/null | tail -n +6 | xargs -r rm --

php artisan down --retry=15 || echo "::warning::No se pudo activar el modo mantenimiento."
echo "Listo para subir v$VERSION."
