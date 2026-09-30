#!/usr/bin/env bash
# Arma dist/genesis.zip para subirlo a un hosting con cPanel.
#
# Uso (Git Bash, desde la raíz del proyecto):
#   bash deploy/build-zip.sh
#
# Variables opcionales:
#   PHP   PHP 8.3+ con las extensiones zip y openssl (por defecto, el de Laragon)
#   NODE  carpeta con Node 20+ (por defecto, node-v22 de Laragon)
#
# Instala vendor/ sin dependencias de desarrollo, compila public/build/,
# comprime solo lo que necesita el servidor y al final deja vendor/ como estaba
# (con las dependencias de desarrollo, para poder correr tests y seeders).
set -euo pipefail

cd "$(dirname "$0")/.."

PHP="${PHP:-/c/laragon/bin/php/php-8.5.5-Win32-vs17-x64/php.exe}"
NODE="${NODE:-/c/laragon/bin/nodejs/node-v22}"
COMPOSER="${COMPOSER:-/c/ProgramData/ComposerSetup/bin/composer.phar}"
TAR="/c/Windows/System32/tar.exe"   # bsdtar de Windows: sabe crear .zip
OUT="dist/genesis.zip"

export PATH="$NODE:$PATH"

echo "== Dependencias de PHP sin las de desarrollo"
"$PHP" "$COMPOSER" install --no-dev --optimize-autoloader --no-interaction

echo "== CSS y JS (public/build)"
npm ci
npm run build

echo "== Comprimiendo"
mkdir -p dist
rm -f "$OUT"
"$TAR" -a -c -f "$OUT" \
    --exclude='./.git' \
    --exclude='./.env' \
    --exclude='./.env.backup' \
    --exclude='./.env.production' \
    --exclude='./node_modules' \
    --exclude='./dist' \
    --exclude='./referencia' \
    --exclude='./tests' \
    --exclude='./docs' \
    --exclude='./deploy' \
    --exclude='./CLAUDE.md' \
    --exclude='./.claude' \
    --exclude='./.vscode' \
    --exclude='./.idea' \
    --exclude='./public/storage' \
    --exclude='./public/hot' \
    --exclude='./bootstrap/cache/*.php' \
    --exclude='./storage/logs/?*.log' \
    --exclude='./storage/framework/cache/data/?*' \
    --exclude='./storage/framework/sessions/?*' \
    --exclude='./storage/framework/views/?*' \
    --exclude='./storage/pail' \
    --exclude='*.key' \
    --exclude='.phpunit.result.cache' \
    .

echo "== Restaurando vendor/ con las dependencias de desarrollo"
"$PHP" "$COMPOSER" install --no-interaction

echo
echo "Listo: $OUT ($(du -h "$OUT" | cut -f1))"
