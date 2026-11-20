#!/usr/bin/env bash
set -euo pipefail

ROLE="${1:-queue}"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"

cd /var/www/html

wait_for_tcp() {
    local host="$1"
    local port="$2"
    while ! php -r "\$fp = @fsockopen('${host}', ${port}, \$errno, \$errstr, 2); if (!\$fp) { exit(1); } fclose(\$fp);" 2>/dev/null; do
        sleep 2
    done
}

echo "[${ROLE}] Wachten op vendor/autoload.php en .env..."
while [ ! -f /var/www/html/vendor/autoload.php ] || [ ! -f /var/www/html/.env ]; do
    sleep 3
done

echo "[${ROLE}] Wachten op database (${DB_HOST}:${DB_PORT})..."
wait_for_tcp "$DB_HOST" "$DB_PORT"

case "$ROLE" in
    queue)
        while true; do
            echo "[queue] Laravel queue worker starten..."
            php artisan queue:work --sleep=3 --tries=3 --max-time=3600 --memory=128 --no-interaction || true
            sleep 2
        done
        ;;
    scheduler)
        while true; do
            echo "[scheduler] Laravel scheduler starten..."
            php artisan schedule:work --no-interaction || true
            sleep 2
        done
        ;;
    reverb)
        while true; do
            if [ -f vendor/laravel/reverb/composer.json ]; then
                echo "[reverb] Laravel Reverb starten op 0.0.0.0:${REVERB_SERVER_PORT:-8080}..."
                php artisan reverb:start --host=0.0.0.0 --port="${REVERB_SERVER_PORT:-8080}" --no-interaction || true
                sleep 3
            else
                sleep 30
            fi
        done
        ;;
    *)
        echo "Onbekende worker-rol: ${ROLE}" >&2
        exit 1
        ;;
esac
