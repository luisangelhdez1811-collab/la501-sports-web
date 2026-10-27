#!/usr/bin/env bash
# =============================================================================
#  Se ejecuta EN EL SERVIDOR (Hostinger) después de subir los archivos nuevos.
#  Lo llama el job "Desplegar en Hostinger" de .github/workflows/ci-cd.yml:
#     ssh servidor 'bash -s' -- <ruta-del-proyecto> <versión> < scripts/deploy/despues.sh
#
#  Aplica las migraciones, limpia las cachés de Laravel y quita el modo mantenimiento.
#  El sitio vuelve a estar en línea pase lo que pase; si algo falla el job queda en rojo.
# =============================================================================
set -euo pipefail

APP="$1"
VERSION="$2"

cd "$APP"
trap 'php artisan up > /dev/null 2>&1 || true' EXIT

mkdir -p storage/framework/{cache/data,sessions,views} storage/logs bootstrap/cache

echo "Migraciones pendientes:"
php artisan migrate:status --pending 2> /dev/null || true
php artisan migrate --force

php artisan optimize:clear
php artisan up

echo "v$VERSION desplegada."
