#!/bin/sh
set -eu

cd /var/www/html

# Runtime-only startup. Do not install packages or run installers here.
chmod -R 777 storage || true
chmod -R 777 vendor || true

# Start Octane without file watcher (watch mode is for local dev only).
exec php artisan octane:start \
  --server=swoole \
  --host=0.0.0.0 \
  --port=8000 \
  --workers="${SWOOLE_WORKERS:-10}" \
  --task-workers="${SWOOLE_TASK_WORKERS:-1}" \
  --max-requests="${SWOOLE_MAX_REQUESTS:-100}"
