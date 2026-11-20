#!/usr/bin/env bash
set -euo pipefail

cd /var/www/html

POLL_INTERVAL="${DEPLOY_POLL_INTERVAL:-60}"
GIT_REMOTE="${GIT_REMOTE:-origin}"
GIT_BRANCH="${GIT_BRANCH:-master}"
DB_HOST="${DB_HOST:-mysql}"
DB_PORT="${DB_PORT:-3306}"
REDIS_HOST="${REDIS_HOST:-redis}"
REDIS_PORT="${REDIS_PORT:-6379}"
FAILED_SHA_FILE="storage/framework/.last_failed_deploy_sha"
SEEDED_FLAG_FILE="storage/framework/.seeded_initial"
DEPLOY_LOG="storage/logs/auto-deploy.log"

log() {
    local msg
    msg="[$(date '+%Y-%m-%d %H:%M:%S')] [deployer] $*"
    echo "$msg"
    if [ -d storage/logs ]; then
        echo "$msg" >> "$DEPLOY_LOG"
    fi
}

wait_for_tcp() {
    local host="$1"
    local port="$2"
    while ! php -r "\$fp = @fsockopen('${host}', ${port}, \$errno, \$errstr, 2); if (!\$fp) { exit(1); } fclose(\$fp);" 2>/dev/null; do
        sleep 2
    done
}

# Kopieer optionele SSH deploy key met veilige 0600 permissies (buiten Synology ACL-mounts)
mkdir -p /var/www/.ssh
chmod 700 /var/www/.ssh
if [ -f /deploy-key/id_ed25519 ]; then
    cp /deploy-key/id_ed25519 /var/www/.ssh/id_ed25519
    chmod 600 /var/www/.ssh/id_ed25519
    cat > /var/www/.ssh/config <<'EOF'
Host github.com
    HostName github.com
    User git
    IdentityFile /var/www/.ssh/id_ed25519
    IdentitiesOnly yes
    StrictHostKeyChecking accept-new
EOF
    chmod 600 /var/www/.ssh/config
fi

mkdir -p storage/logs storage/framework/cache storage/framework/sessions storage/framework/views bootstrap/cache
git config core.fileMode false || true

if [ -f .env ] && ! grep -Eq '^APP_KEY=[^[:space:]]+' .env; then
    log "APP_KEY ontbrak in .env — nieuwe sleutel genereren..."
    GENERATED_KEY="base64:$(php -r 'echo base64_encode(random_bytes(32));')"
    sed -i "s|^APP_KEY=.*|APP_KEY=${GENERATED_KEY}|" .env
fi

log "Wachten op MySQL (${DB_HOST}:${DB_PORT}) en Redis (${REDIS_HOST}:${REDIS_PORT})..."
wait_for_tcp "$DB_HOST" "$DB_PORT"
wait_for_tcp "$REDIS_HOST" "$REDIS_PORT"

# Eerste installatie indien vendor/ of Vite build nog ontbreekt
if [ ! -f vendor/autoload.php ] || [ ! -f public/build/manifest.json ]; then
    BOOTSTRAP_ARGS=("--skip-git" "--no-down")
    if [ "${AUTO_SEED_FIRST_BOOT:-true}" = "true" ] && [ ! -f "$SEEDED_FLAG_FILE" ]; then
        BOOTSTRAP_ARGS+=("--seed")
    fi
    log "Eerste bootstrap-deploy uitvoeren (${BOOTSTRAP_ARGS[*]})..."
    if bash ./deploy.sh "${BOOTSTRAP_ARGS[@]}" 2>&1 | tee -a "$DEPLOY_LOG"; then
        touch "$SEEDED_FLAG_FILE"
        rm -f "$FAILED_SHA_FILE"
        log "✅ Eerste bootstrap-deploy afgerond."
    else
        log "❌ Eerste bootstrap-deploy mislukt."
    fi
fi

log "Auto-deployer actief: controleert elke ${POLL_INTERVAL}s ${GIT_REMOTE}/${GIT_BRANCH} op nieuwe commits."

while true; do
    sleep "$POLL_INTERVAL"

    if ! timeout 30 git fetch --quiet "$GIT_REMOTE" "$GIT_BRANCH" 2>>"$DEPLOY_LOG"; then
        log "⚠️  Kon ${GIT_REMOTE}/${GIT_BRANCH} tijdelijk niet bereiken; volgende poging over ${POLL_INTERVAL}s."
        continue
    fi

    LOCAL_SHA="$(git rev-parse HEAD 2>/dev/null || echo '')"
    REMOTE_SHA="$(git rev-parse "${GIT_REMOTE}/${GIT_BRANCH}" 2>/dev/null || echo '')"

    if [ -z "$LOCAL_SHA" ] || [ -z "$REMOTE_SHA" ] || [ "$LOCAL_SHA" = "$REMOTE_SHA" ]; then
        continue
    fi

    LAST_FAILED_SHA=""
    if [ -f "$FAILED_SHA_FILE" ]; then
        LAST_FAILED_SHA="$(cat "$FAILED_SHA_FILE" 2>/dev/null || echo '')"
    fi

    if [ "$REMOTE_SHA" = "$LAST_FAILED_SHA" ]; then
        continue
    fi

    log "🚀 Nieuwe commit(s) gedetecteerd op ${GIT_REMOTE}/${GIT_BRANCH} (${LOCAL_SHA:0:7} -> ${REMOTE_SHA:0:7}):"
    git log --oneline "${LOCAL_SHA}..${REMOTE_SHA}" 2>&1 | tee -a "$DEPLOY_LOG" || true

    set +e
    GIT_REMOTE="$GIT_REMOTE" GIT_BRANCH="$GIT_BRANCH" bash ./deploy.sh 2>&1 | tee -a "$DEPLOY_LOG"
    DEPLOY_EXIT=${PIPESTATUS[0]}
    set -e

    if [ "$DEPLOY_EXIT" -eq 0 ]; then
        rm -f "$FAILED_SHA_FILE"
        log "✅ Deploy naar ${REMOTE_SHA:0:7} succesvol afgerond!"
        exec bash /var/www/html/docker/nas/auto-deploy.sh
    else
        echo "$REMOTE_SHA" > "$FAILED_SHA_FILE"
        log "❌ Deploy naar ${REMOTE_SHA:0:7} mislukt (exit ${DEPLOY_EXIT}). Wacht op nieuwe fix-commit."
    fi
done
