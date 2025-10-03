#!/bin/sh
set -e

# docker-entrypoint.sh
# Ensures .env and APP_KEY exist, then execs the container CMD.

cd /var/www || exit 1

# If .env doesn't exist but .env.docker.prod/.env.docker.dev does, copy it
if [ ! -f .env ]; then
  if [ -f .env.docker.prod ]; then
    cp .env.docker.prod .env
  elif [ -f .env.docker.dev ]; then
    cp .env.docker.dev .env
  fi
fi

# Determine runtime environment (default to production if not provided)
APP_ENV=${APP_ENV:-$(grep '^APP_ENV=' .env 2>/dev/null | cut -d'=' -f2 || echo production)}

# Ensure APP_KEY handling is appropriate for the environment
if [ -f .env ]; then
  APP_KEY_LINE=$(grep '^APP_KEY=' .env || true)
  if [ "$APP_ENV" = "production" ]; then
    # In production require an explicit APP_KEY via environment variable or .env (do not auto-generate)
    if [ -n "$APP_KEY" ]; then
      echo "[entrypoint] Using APP_KEY provided in environment for production"
      # ensure .env contains the provided APP_KEY
      grep -v '^APP_KEY=' .env > .env.tmp || true
      echo "APP_KEY=$APP_KEY" >> .env.tmp
      mv .env.tmp .env
    elif [ -n "$APP_KEY_LINE" ]; then
      # If the APP_KEY in .env looks like a placeholder (e.g. contains YOUR_ or base64:YOUR_...),
      # treat it as not valid and generate a runtime key only as a last-resort fallback so the
      # application doesn't crash with an unsupported cipher error. Prefer an explicit APP_KEY
      # provided via environment in production; auto-generation here is a pragmatic fallback.
      echo "$APP_KEY_LINE" | grep -q 'YOUR_' 2>/dev/null
      if [ $? -eq 0 ]; then
        if [ -n "$APP_KEY" ]; then
          echo "[entrypoint] Using APP_KEY provided in environment for production"
          grep -v '^APP_KEY=' .env > .env.tmp || true
          echo "APP_KEY=$APP_KEY" >> .env.tmp
          mv .env.tmp .env
        else
          echo "[entrypoint] WARNING: APP_KEY in .env appears to be a placeholder; generating a temporary APP_KEY for startup"
          NEW_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));") || NEW_KEY=""
          if [ -n "$NEW_KEY" ]; then
            grep -v '^APP_KEY=' .env > .env.tmp || true
            echo "APP_KEY=$NEW_KEY" >> .env.tmp
            mv .env.tmp .env
            # Export so subprocesses / php can read it from environment too
            export APP_KEY="$NEW_KEY"
          else
            echo "[entrypoint] ERROR: failed to generate APP_KEY and no valid APP_KEY provided"
            exit 1
          fi
        fi
      else
        echo "[entrypoint] Using APP_KEY found in .env for production"
      fi
    else
      echo "[entrypoint] ERROR: APP_KEY is required in production. Set APP_KEY as an environment variable or in .env.docker.prod"
      exit 1
    fi
  else
    # Non-production: generate APP_KEY if missing or placeholder
    if [ -z "$APP_KEY_LINE" ] || echo "$APP_KEY_LINE" | grep -q 'YOUR_' ; then
      echo "[entrypoint] Generating APP_KEY in .env (non-production)"
      NEW_KEY=$(php -r "echo 'base64:'.base64_encode(random_bytes(32));") || NEW_KEY=""
      if [ -n "$NEW_KEY" ]; then
        if [ -f .env ]; then
          grep -v '^APP_KEY=' .env > .env.tmp || true
        else
          touch .env.tmp
        fi
        echo "APP_KEY=$NEW_KEY" >> .env.tmp
        mv .env.tmp .env
      else
        echo "[entrypoint] WARNING: failed to generate APP_KEY"
      fi
    fi
  fi

  # Substitute ${VAR} placeholders in .env using environment variables (if any).
  # If a placeholder has no corresponding env var, remove that line so Laravel can use its default.
  if command -v php >/dev/null 2>&1; then
    echo "[entrypoint] Expanding any \${VAR} placeholders in .env using environment variables"
    php <<'PHP'
<?php
$path = getcwd()."/.env";
if (!file_exists($path)) exit(0);
$lines = file($path, FILE_IGNORE_NEW_LINES);
$out = [];
foreach ($lines as $line) {
  if (preg_match('/^([A-Z0-9_]+)=(.*)$/', $line, $m)) {
    $key = $m[1];
    $val = $m[2];
    // If value contains ${VAR}, substitute with getenv(VAR) when present
    $new = preg_replace_callback('/\\$\\{([A-Z0-9_]+)\\}/', function($mm){ $v = getenv($mm[1]); return $v === false ? '' : $v; }, $val);
    // If original had a placeholder and the env var was not set, skip the line so Laravel falls back to defaults
    if (preg_match('/\\$\\{([A-Z0-9_]+)\\}/', $val, $mm) && getenv($mm[1]) === false) {
      continue;
    }
    $out[] = "$key=$new";
  } else {
    $out[] = $line;
  }
}
file_put_contents($path, implode(PHP_EOL, $out).PHP_EOL);
PHP
  fi
  # If Redis is not configured or not reachable, switch to file drivers BEFORE running any artisan commands
  # so that cache:clear / optimize:clear do not attempt to connect to Redis and fail startup.
  REDIS_HOST=${REDIS_HOST:-$(grep '^REDIS_HOST=' .env 2>/dev/null | cut -d'=' -f2 || true)}
  REDIS_PORT=${REDIS_PORT:-$(grep '^REDIS_PORT=' .env 2>/dev/null | cut -d'=' -f2 || echo 6379)}
  placeholder=0
  case "$REDIS_HOST" in
    *'${'*) placeholder=1 ;;
    *) placeholder=0 ;;
  esac
  if [ -z "$REDIS_HOST" ] || [ "$placeholder" -eq 1 ]; then
    echo "[entrypoint] Redis not configured or placeholder detected; switching cache/session/queue to file/sync drivers before artisan commands"
    grep -v '^CACHE_DRIVER=' .env > .env.tmp || true
    echo "CACHE_DRIVER=file" >> .env.tmp
    grep -v '^SESSION_DRIVER=' .env.tmp > .env.tmp2 || true
    echo "SESSION_DRIVER=file" >> .env.tmp2
    grep -v '^QUEUE_CONNECTION=' .env.tmp2 > .env.tmp || true
    echo "QUEUE_CONNECTION=sync" >> .env.tmp
    mv .env.tmp .env
    export CACHE_DRIVER=file
    export SESSION_DRIVER=file
    export QUEUE_CONNECTION=sync
  else
    # Try opening a TCP connection to REDIS_HOST:REDIS_PORT using PHP; timeout 1s
    if command -v php >/dev/null 2>&1; then
      php -r "\$h=getenv('REDIS_HOST')?:'${REDIS_HOST}'; \$p=getenv('REDIS_PORT')?:'${REDIS_PORT}'; \$s=@fsockopen(\$h, (int)\$p, \$e, \$err, 1); if(\$s){ fclose(\$s); exit(0);} exit(1);" >/dev/null 2>&1
      if [ $? -ne 0 ]; then
        echo "[entrypoint] Redis ${REDIS_HOST}:${REDIS_PORT} unreachable; switching cache/session/queue to file/sync drivers before artisan commands"
        grep -v '^CACHE_DRIVER=' .env > .env.tmp || true
        echo "CACHE_DRIVER=file" >> .env.tmp
        grep -v '^SESSION_DRIVER=' .env.tmp > .env.tmp2 || true
        echo "SESSION_DRIVER=file" >> .env.tmp2
        grep -v '^QUEUE_CONNECTION=' .env.tmp2 > .env.tmp || true
        echo "QUEUE_CONNECTION=sync" >> .env.tmp
        mv .env.tmp .env
        export CACHE_DRIVER=file
        export SESSION_DRIVER=file
        export QUEUE_CONNECTION=sync
      else
        echo "[entrypoint] Redis ${REDIS_HOST}:${REDIS_PORT} reachable; keeping configured drivers"
      fi
    fi
  fi

  # Clear Laravel config and views to pick up new APP_KEY (now that drivers are fixed)
  if command -v php >/dev/null 2>&1 && [ -f artisan ]; then
    echo "[entrypoint] Clearing Laravel config, views and compiled caches to pick up new APP_KEY"
    # optimize:clear will clear compiled, route, config and other caches that may include serialized closures
    php artisan optimize:clear || true
    # ensure routes cache is removed (contains serialized closures)
    php artisan route:clear || true
    php artisan config:clear || true
    php artisan view:clear || true
    # If the generated swagger docs file is missing, attempt to generate it at runtime
    DOCS_FILE="storage/api-docs/api-docs.json"
    if [ -f artisan ] && command -v php >/dev/null 2>&1; then
      if [ ! -f "$DOCS_FILE" ]; then
        echo "[entrypoint] Swagger docs not found at $DOCS_FILE; attempting to generate with l5-swagger"
        # Try to generate the docs; failure should not abort startup
        php artisan l5-swagger:generate --no-interaction --quiet || echo "[entrypoint] WARNING: l5-swagger:generate failed or is not available"
      else
        echo "[entrypoint] Swagger docs present; skipping generation"
      fi
    fi
  fi
fi

# If Redis is not configured or not reachable, fall back to file drivers to avoid runtime exceptions
if [ -f .env ]; then
  # Read REDIS_HOST and REDIS_PORT from environment or .env
  REDIS_HOST=${REDIS_HOST:-$(grep '^REDIS_HOST=' .env 2>/dev/null | cut -d'=' -f2 || true)}
  REDIS_PORT=${REDIS_PORT:-$(grep '^REDIS_PORT=' .env 2>/dev/null | cut -d'=' -f2 || echo 6379)}
  # If REDIS_HOST is empty or contains a ${...} placeholder, consider Redis unavailable
  placeholder=0
  case "$REDIS_HOST" in
    *'${'*) placeholder=1 ;;
    *) placeholder=0 ;;
  esac
  if [ -z "$REDIS_HOST" ] || [ "$placeholder" -eq 1 ]; then
    echo "[entrypoint] Redis not configured; switching cache/session/queue to file/sync drivers"
    grep -v '^CACHE_DRIVER=' .env > .env.tmp || true
    echo "CACHE_DRIVER=file" >> .env.tmp
    grep -v '^SESSION_DRIVER=' .env.tmp > .env.tmp2 || true
    echo "SESSION_DRIVER=file" >> .env.tmp2
    grep -v '^QUEUE_CONNECTION=' .env.tmp2 > .env.tmp || true
    echo "QUEUE_CONNECTION=sync" >> .env.tmp
    mv .env.tmp .env
  else
    # Try opening a TCP connection to REDIS_HOST:REDIS_PORT using PHP; timeout 1s
    if command -v php >/dev/null 2>&1; then
      php -r "\$h=getenv('REDIS_HOST')?:'${REDIS_HOST}'; \$p=getenv('REDIS_PORT')?:'${REDIS_PORT}'; \$s=@fsockopen(\$h, (int)\$p, \$e, \$err, 1); if(\$s){ fclose(\$s); exit(0);} exit(1);" >/dev/null 2>&1
      if [ $? -ne 0 ]; then
        echo "[entrypoint] Redis ${REDIS_HOST}:${REDIS_PORT} unreachable; switching cache/session/queue to file/sync drivers"
        grep -v '^CACHE_DRIVER=' .env > .env.tmp || true
        echo "CACHE_DRIVER=file" >> .env.tmp
        grep -v '^SESSION_DRIVER=' .env.tmp > .env.tmp2 || true
        echo "SESSION_DRIVER=file" >> .env.tmp2
        grep -v '^QUEUE_CONNECTION=' .env.tmp2 > .env.tmp || true
        echo "QUEUE_CONNECTION=sync" >> .env.tmp
        mv .env.tmp .env
      else
        echo "[entrypoint] Redis ${REDIS_HOST}:${REDIS_PORT} reachable; keeping configured drivers"
      fi
    fi
  fi
fi

echo "[entrypoint] Running: $@"

# Replace shell with the requested command so signals are forwarded
exec "$@"
