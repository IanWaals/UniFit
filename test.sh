#!/bin/bash
<<<<<<< HEAD

PORT="8080"
cd frontend
echo "Starting Development Webserver on port: $PORT"
python3 -m http.server $PORT
=======
# Starts a local development server for the PHP version.
# Needs PHP 8.1+ with the curl extension (Fedora: sudo dnf install php-cli php-common).
# No PHP installed? Uses podman instead.

PORT="8080"
cd "$(dirname "$0")"

if [ ! -f src/config.php ]; then
    echo "src/config.php missing: copy src/config.php.template to src/config.php first."
    exit 1
fi

echo "Starting Development Webserver on http://localhost:$PORT/login.php"
if command -v php >/dev/null; then
    php -S "localhost:$PORT" -t public
else
    podman run --rm -it -p "$PORT:$PORT" -v "$PWD:/app:Z" -w /app docker.io/library/php:8.3-cli \
        php -S "0.0.0.0:$PORT" -t public
fi
>>>>>>> e6f02c9 (PHP)
