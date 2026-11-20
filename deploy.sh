#!/usr/bin/env bash
set -euo pipefail

# ==============================================================================
# Heimdall Kiosk — Productie Deploy Script
# ==============================================================================
# Gebruik:
#   ./deploy.sh [--seed] [--skip-git] [--no-down]
#
# Optionele omgevingsvariabelen:
#   GIT_REMOTE       Git remote naam (standaard: origin)
#   GIT_BRANCH       Git branch naam (standaard: huidige branch of master)
#   PHP_BIN          PHP binary (standaard: php)
#   COMPOSER_BIN     Composer binary (standaard: composer)
#   NPM_BIN          npm binary (standaard: npm)
#   PHP_FPM_SERVICE  Systemd PHP-FPM service om te herladen (bijv. php8.3-fpm)
# ==============================================================================

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$APP_DIR"

PHP_BIN="${PHP_BIN:-php}"
COMPOSER_BIN="${COMPOSER_BIN:-composer}"
NPM_BIN="${NPM_BIN:-npm}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
DEFAULT_BRANCH="$(git symbolic-ref --quiet --short HEAD 2>/dev/null || echo master)"
GIT_BRANCH="${GIT_BRANCH:-$DEFAULT_BRANCH}"
PHP_FPM_SERVICE="${PHP_FPM_SERVICE:-}"

RUN_SEED=false
SKIP_GIT=false
USE_MAINTENANCE=true
MAINTENANCE_ACTIVE=false

usage() {
    cat <<'EOF'
Gebruik: ./deploy.sh [opties]

Opties:
  --seed       Voer database seeders uit na de migraties (eerste installatie)
  --skip-git   Sla git fetch/pull over (bijv. wanneer CI/CD dit al doet)
  --no-down    Zet de applicatie niet in onderhoudsmodus tijdens de deploy
  -h, --help   Toon deze hulptekst

Omgevingsvariabelen:
  GIT_REMOTE, GIT_BRANCH, PHP_BIN, COMPOSER_BIN, NPM_BIN, PHP_FPM_SERVICE
EOF
}

for arg in "$@"; do
    case "$arg" in
        --seed)
            RUN_SEED=true
            ;;
        --skip-git)
            SKIP_GIT=true
            ;;
        --no-down)
            USE_MAINTENANCE=false
            ;;
        -h|--help)
            usage
            exit 0
            ;;
        *)
            echo "❌ Onbekende optie: $arg" >&2
            usage >&2
            exit 1
            ;;
    esac
done

step() {
    printf '\n==> %s\n' "$1"
}

cleanup() {
    local exit_code=$?
    if [ "$MAINTENANCE_ACTIVE" = true ] && [ -f vendor/autoload.php ]; then
        echo "⚠️  Deploy onderbroken (exit ${exit_code}) — onderhoudsmodus wordt opgeheven..." >&2
        "$PHP_BIN" artisan up --no-interaction || true
    fi
    exit "$exit_code"
}
trap cleanup EXIT

# 1. Pre-flight controles
step "Omgeving controleren"

for bin in "$PHP_BIN" "$COMPOSER_BIN" "$NPM_BIN"; do
    if ! command -v "$bin" >/dev/null 2>&1; then
        echo "❌ Vereist commando niet gevonden: $bin" >&2
        exit 1
    fi
done

if [ "$SKIP_GIT" = false ] && ! command -v git >/dev/null 2>&1; then
    echo "❌ Vereist commando niet gevonden: git" >&2
    exit 1
fi

if [ ! -f .env ]; then
    echo "❌ Bestand .env ontbreekt in ${APP_DIR}." >&2
    echo "   Kopieer eerst .env.example naar .env, configureer MySQL/Redis en voer 'php artisan key:generate' uit." >&2
    exit 1
fi

if ! grep -Eq '^APP_KEY=[^[:space:]]+' .env; then
    echo "❌ APP_KEY is nog niet ingesteld in .env. Voer eerst '${PHP_BIN} artisan key:generate' uit." >&2
    exit 1
fi

# 2. Onderhoudsmodus inschakelen (met automatische 5s refresh voor Kiosk-panelen)
if [ "$USE_MAINTENANCE" = true ] && [ -f vendor/autoload.php ]; then
    step "Onderhoudsmodus inschakelen"
    "$PHP_BIN" artisan down --retry=15 --refresh=5 --no-interaction
    MAINTENANCE_ACTIVE=true
fi

# 3. Laatste code ophalen via Git
if [ "$SKIP_GIT" = false ]; then
    step "Code bijwerken vanaf ${GIT_REMOTE}/${GIT_BRANCH}"
    git fetch "$GIT_REMOTE" "$GIT_BRANCH"
    git pull --ff-only "$GIT_REMOTE" "$GIT_BRANCH"
fi

# 4. PHP-dependencies installeren (vóór Vite build i.v.m. Filament CSS imports uit vendor/)
step "Composer dependencies installeren (productie)"
COMPOSER_ALLOW_SUPERUSER=1 "$COMPOSER_BIN" install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --optimize-autoloader

# 5. Frontend assets compileren (Vite + Tailwind CSS v4 + Filament theme)
step "Frontend dependencies installeren en assets bouwen"
rm -f public/hot

if [ -f package-lock.json ]; then
    "$NPM_BIN" ci --include=dev --no-audit --no-fund
else
    "$NPM_BIN" install --include=dev --no-audit --no-fund
fi

"$NPM_BIN" run build

# 6. Storage symlink aanmaken indien nodig
if [ ! -e public/storage ] && [ ! -L public/storage ]; then
    step "Storage symlink aanmaken"
    "$PHP_BIN" artisan storage:link --no-interaction
fi

# 7. Database migraties (en optioneel seeders) uitvoeren
step "Database migraties uitvoeren"
"$PHP_BIN" artisan migrate --force --no-interaction

if [ "$RUN_SEED" = true ]; then
    step "Database seeders uitvoeren"
    "$PHP_BIN" artisan db:seed --force --no-interaction
fi

# 8. Framework, Blade Icons en Filament optimaliseren & caches opbouwen
step "Applicatie- en Filament-caches optimaliseren"
"$PHP_BIN" artisan optimize:clear --no-interaction
"$PHP_BIN" artisan optimize --no-interaction

# 9. Achtergrondprocessen (queue/scheduler) en optioneel PHP-FPM herladen
step "Achtergrondservices herladen"
"$PHP_BIN" artisan reload --no-interaction

if [ -n "$PHP_FPM_SERVICE" ]; then
    step "PHP-FPM herladen (${PHP_FPM_SERVICE})"
    sudo -n systemctl reload "$PHP_FPM_SERVICE"
fi

# 10. Onderhoudsmodus uitschakelen
if [ "$MAINTENANCE_ACTIVE" = true ]; then
    step "Applicatie weer live zetten"
    "$PHP_BIN" artisan up --no-interaction
    MAINTENANCE_ACTIVE=false
fi

printf '\n✅ Deploy succesvol afgerond!\n'
