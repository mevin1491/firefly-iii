FROM ubuntu:24.04

ENV DEBIAN_FRONTEND=noninteractive

# System toolchain for Firefly III (Laravel):
#   - PHP 8.4 CLI + the extensions required by composer.json
#   - Composer (PHP dependency manager)
#   - Node.js 22 (builds the v1 Laravel Mix and v2 Vite frontends)
# The application uses a local SQLite database for development (php8.4-sqlite3),
# so no separate database server is required.
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates curl git unzip gnupg software-properties-common \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && add-apt-repository -y ppa:ondrej/php \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        nodejs \
        php8.4-cli php8.4-common php8.4-mysql php8.4-xml php8.4-mbstring \
        php8.4-curl php8.4-intl php8.4-bcmath php8.4-zip php8.4-gd \
        php8.4-readline php8.4-sqlite3 php8.4-tokenizer php8.4-fileinfo \
    && curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && rm -rf /var/lib/apt/lists/*

USER ubuntu
