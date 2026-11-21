#!/usr/bin/env bash
set -euo pipefail

## ==============================================================================
## Script para generar o sincronizar el archivo .env de producción en GoCD (Odin)
## ==============================================================================

DB_USERNAME="${DB_USERNAME:-${1:-fryntiz}}"
DB_PASSWORD="${DB_PASSWORD:-${2:-}}"

WORKSCRIPT="$(pwd)"

if [[ $(whoami) == "root" ]]; then
    echo "❌ Error: No puedes ejecutar este script como root." >&2
    exit 1
fi

if [[ ! -f "${WORKSCRIPT}/.env.example.production" ]] ||
   [[ ! -d "${WORKSCRIPT}/scripts" ]] ||
   [[ ! -f "${WORKSCRIPT}/scripts/functions.sh" ]]; then
    echo "❌ Error: Este script solo puede ejecutarse desde la raíz del proyecto." >&2
    exit 1
fi

source "${WORKSCRIPT}/scripts/functions.sh"

PREV_ENV_BACKUP=""
if [[ -f "${WORKSCRIPT}/.env" ]]; then
    PREV_ENV_BACKUP=$(cat "${WORKSCRIPT}/.env")
fi
PUBLIC_ENV_FILE="/var/www/public/${PATHNAME:-feed.raupulus.dev}/.env"

existing_env_var() {
    local KEY="${1}"
    local VAL=""
    if [[ -n "${PREV_ENV_BACKUP}" ]]; then
        VAL=$(printf '%s\n' "${PREV_ENV_BACKUP}" | grep -E "^${KEY}=" | head -n 1 | cut -d'=' -f2- || true)
    fi
    if [[ -z "${VAL}" ]] && [[ -f "${PUBLIC_ENV_FILE}" ]]; then
        VAL=$(grep -E "^${KEY}=" "${PUBLIC_ENV_FILE}" | head -n 1 | cut -d'=' -f2- || true)
    fi
    printf '%s' "${VAL}"
}

EXISTING_APP_KEY="${APP_KEY:-$(existing_env_var APP_KEY)}"
EXISTING_REVERB_SECRET="${REVERB_APP_SECRET:-$(existing_env_var REVERB_APP_SECRET)}"
if [[ -z "${EXISTING_REVERB_SECRET}" ]]; then
    EXISTING_REVERB_SECRET=$(openssl rand -hex 24)
fi

cp -f "${WORKSCRIPT}/.env.example.production" "${WORKSCRIPT}/.env"
chmod ug+rw "${WORKSCRIPT}/.env"

if [[ -n "${EXISTING_APP_KEY}" ]]; then
    replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_KEY" "${EXISTING_APP_KEY}"
fi

## General
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_NAME" "${APP_NAME:-beacon-sift}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_ENV" "${APP_ENV:-production}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_DEBUG" "${APP_DEBUG:-false}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_URL" "${APP_URL:-https://feed.raupulus.dev}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_TIMEZONE" "${APP_TIMEZONE:-Europe/Madrid}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_LOCALE" "${APP_LOCALE:-es}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "APP_FALLBACK_LOCALE" "${APP_FALLBACK_LOCALE:-en}"

## Base de datos PostgreSQL
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "DB_CONNECTION" "${DB_CONNECTION:-pgsql}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "DB_HOST" "${DB_HOST:-127.0.0.1}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "DB_PORT" "${DB_PORT:-5432}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "DB_DATABASE" "${DB_DATABASE:-beacon_sift}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "DB_USERNAME" "${DB_USERNAME}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "DB_PASSWORD" "${DB_PASSWORD}"

## Caché y Sesiones (Redis)
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "CACHE_STORE" "${CACHE_STORE:-redis}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "CACHE_PREFIX" "${CACHE_PREFIX:-beacon_sift_}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "SESSION_DRIVER" "${SESSION_DRIVER:-redis}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "SESSION_LIFETIME" "${SESSION_LIFETIME:-120}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "SESSION_SECURE_COOKIE" "${SESSION_SECURE_COOKIE:-true}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REDIS_CLIENT" "${REDIS_CLIENT:-phpredis}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REDIS_HOST" "${REDIS_HOST:-127.0.0.1}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REDIS_PORT" "${REDIS_PORT:-6379}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REDIS_PASSWORD" "${REDIS_PASSWORD:-}"

## Broadcasting / Laravel Reverb
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "QUEUE_CONNECTION" "${QUEUE_CONNECTION:-sync}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BROADCAST_CONNECTION" "${BROADCAST_CONNECTION:-reverb}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_APP_ID" "${REVERB_APP_ID:-beacon-sift}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_APP_KEY" "${REVERB_APP_KEY:-beacon-sift-public-key}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_APP_SECRET" "${EXISTING_REVERB_SECRET}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_HOST" "${REVERB_HOST:-feed.raupulus.dev}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_PORT" "${REVERB_PORT:-443}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_SCHEME" "${REVERB_SCHEME:-https}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_SERVER_HOST" "${REVERB_SERVER_HOST:-127.0.0.1}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "REVERB_SERVER_PORT" "${REVERB_SERVER_PORT:-8082}"

## Configuración Beacon Sift
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BEACON_FETCH_LOGS_RETENTION_DAYS" "${BEACON_FETCH_LOGS_RETENTION_DAYS:-30}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BEACON_ITEMS_RETENTION_DAYS" "${BEACON_ITEMS_RETENTION_DAYS:-0}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BEACON_SNIPPET_MAX_LENGTH" "${BEACON_SNIPPET_MAX_LENGTH:-160}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BEACON_HTTP_TIMEOUT" "${BEACON_HTTP_TIMEOUT:-15}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BEACON_BROADCAST_CHANNEL" "${BEACON_BROADCAST_CHANNEL:-beacon.news}"
replace_or_add_var_in_file "${WORKSCRIPT}/.env" "BEACON_CONTACT_EMAIL" "${BEACON_CONTACT_EMAIL:-public@raupulus.dev}"

echo "✅ Archivo .env de producción generado correctamente para beacon-sift-web."
