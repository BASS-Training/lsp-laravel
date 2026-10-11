#!/usr/bin/env bash
# Deploy LSP FIT (Laravel) di server Apache + mod_php, tanpa Docker.
#
#   ./deploy.sh doctor        periksa server dan .env, tidak mengubah apa pun
#   ./deploy.sh deploy        backup -> git pull -> composer -> cache -> health check
#   ./deploy.sh uploads-sync  salin upload dari situs PHP lama (tanpa menghapus)
#   ./deploy.sh rollback      kembali ke commit sebelum deploy terakhir
#
# Database lama dipakai apa adanya: skrip ini TIDAK PERNAH menjalankan `artisan migrate`.
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/lsp-laravel}"
LEGACY_DIR="${LEGACY_DIR:-/var/www/sertifikasifit.com}"
BRANCH="${BRANCH:-main}"
BACKUP_DIR="${BACKUP_DIR:-/var/backups/lspfit-laravel}"
KEEP_BACKUPS="${KEEP_BACKUPS:-5}"
HEALTH_URL="${HEALTH_URL:-http://127.0.0.1/}"
HEALTH_HOST="${HEALTH_HOST:-sertifikasifit.com}"
WEB_USER="${WEB_USER:-www-data}"

cd "$APP_DIR"
log() { printf '\n==> %s\n' "$*"; }
fail() { printf 'GAGAL: %s\n' "$*" >&2; exit 1; }
env_value() { grep -E "^$1=" .env | head -1 | cut -d= -f2- | tr -d '"'; }

doctor() {
    local problems=0
    check() { if ! "${@:2}" >/dev/null 2>&1; then printf '  [X] %s\n' "$1"; problems=$((problems + 1)); else printf '  [ok] %s\n' "$1"; fi; }

    log "Periksa server"
    check "PHP >= 8.3" php -r 'exit(PHP_VERSION_ID >= 80300 ? 0 : 1);'
    check "ekstensi pdo_mysql, mbstring, fileinfo, gd" php -r 'foreach (["pdo_mysql","mbstring","fileinfo","gd"] as $e) { if (!extension_loaded($e)) exit(1); }'
    check "composer, git, mysqldump, rsync" bash -c 'command -v composer git mysqldump rsync'
    check "mod_rewrite aktif" bash -c 'apache2ctl -M 2>/dev/null | grep -q rewrite_module'

    log "Periksa .env"
    check ".env ada" test -f .env
    if [ -f .env ]; then
        check "APP_ENV=production" test "$(env_value APP_ENV)" = production
        check "APP_DEBUG=false" test "$(env_value APP_DEBUG)" = false
        check "APP_KEY terisi" test -n "$(env_value APP_KEY)"
        check "SESSION_DRIVER=file" test "$(env_value SESSION_DRIVER)" = file
        check "DB_USERNAME bukan root" test "$(env_value DB_USERNAME)" != root
        check "koneksi database berhasil" php artisan db:show --counts --no-interaction
    fi

    log "Periksa folder tulis"
    for dir in storage bootstrap/cache public/assets/uploads; do
        check "$dir dapat ditulis $WEB_USER" sudo -u "$WEB_USER" test -w "$dir"
    done
    return $((problems > 0))
}

backup() {
    local stamp; stamp="$(date +%Y%m%d-%H%M%S)"
    mkdir -p "$BACKUP_DIR"
    log "Backup database dan upload ke $BACKUP_DIR/$stamp"
    mkdir -p "$BACKUP_DIR/$stamp"
    git rev-parse HEAD > "$BACKUP_DIR/$stamp/commit"
    cp "$BACKUP_DIR/$stamp/commit" "$BACKUP_DIR/last-commit"
    # Kredensial dibaca dari .env lewat file opsi sementara agar tidak muncul di daftar proses.
    local cnf; cnf="$(mktemp)"; chmod 600 "$cnf"
    printf '[client]\nhost=%s\nport=%s\nuser=%s\npassword="%s"\n' \
        "$(env_value DB_HOST)" "$(env_value DB_PORT)" "$(env_value DB_USERNAME)" "$(env_value DB_PASSWORD)" > "$cnf"
    mysqldump --defaults-extra-file="$cnf" --single-transaction --no-tablespaces "$(env_value DB_DATABASE)" | gzip > "$BACKUP_DIR/$stamp/db.sql.gz"
    rm -f "$cnf"
    tar -czf "$BACKUP_DIR/$stamp/uploads.tar.gz" -C public/assets uploads
    # Simpan hanya $KEEP_BACKUPS backup terbaru.
    ls -1d "$BACKUP_DIR"/20* 2>/dev/null | head -n "-$KEEP_BACKUPS" | xargs -r rm -rf
}

permissions() {
    chown -R "$WEB_USER":"$WEB_USER" storage bootstrap/cache public/assets/uploads
    find storage bootstrap/cache public/assets/uploads -type d -exec chmod 2775 {} +
    chmod 640 .env && chown root:"$WEB_USER" .env
}

health_check() {
    local code
    code="$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $HEALTH_HOST" "$HEALTH_URL" || true)"
    [ "$code" = 200 ] || return 1
    code="$(curl -s -o /dev/null -w '%{http_code}' -H "Host: $HEALTH_HOST" "${HEALTH_URL%/}/api/kegiatan.php" || true)"
    [ "$code" = 200 ]
}

build() {
    composer install --no-dev --optimize-autoloader --no-interaction
    php artisan optimize:clear --no-interaction
    php artisan storage:link --no-interaction >/dev/null 2>&1 || true
    php artisan optimize --no-interaction
    permissions
}

deploy() {
    doctor || fail "doctor menemukan masalah; perbaiki dulu."
    [ -z "$(git status --porcelain --untracked-files=no)" ] || fail "ada perubahan lokal yang belum di-commit."
    backup
    log "Tarik kode ($BRANCH)"
    git fetch origin "$BRANCH"
    git merge --ff-only "origin/$BRANCH"
    build
    log "Health check"
    if health_check; then
        echo "Deploy berhasil di $(git rev-parse --short HEAD)."
    else
        echo "Health check gagal, rollback otomatis." >&2
        rollback
        exit 1
    fi
}

rollback() {
    local previous; previous="$(cat "$BACKUP_DIR/last-commit" 2>/dev/null)" || fail "tidak ada commit backup."
    log "Rollback kode ke $previous (database dan upload tidak diubah)"
    git checkout --detach "$previous"
    build
    health_check && echo "Rollback selesai." || fail "health check masih gagal setelah rollback."
}

uploads_sync() {
    [ -d "$LEGACY_DIR/assets/uploads" ] || fail "$LEGACY_DIR/assets/uploads tidak ditemukan."
    log "Salin upload dari situs lama (tanpa menghapus apa pun)"
    rsync -a --exclude '.htaccess' --exclude '.gitkeep' "$LEGACY_DIR/assets/uploads/" public/assets/uploads/
    permissions
}

case "${1:-}" in
    doctor) doctor ;;
    deploy) deploy ;;
    rollback) rollback ;;
    uploads-sync) uploads_sync ;;
    *) sed -n 2,9p "$0"; exit 1 ;;
esac
