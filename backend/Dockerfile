# Symfony 7.4 backend TerraLink
# Serveur de production : FrankenPHP (Caddy + PHP) — multi-threadé, gère les requêtes concurrentes.
# (Remplace l'ancien `php -S` mono-thread qui provoquait ERR_CONNECTION_RESET / 404 sous charge.)
FROM dunglas/frankenphp:1-php8.3

# Dépendances système + extensions PHP.
# La base de production de Render est PostgreSQL -> pdo_pgsql / pgsql.
RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libicu-dev libzip-dev libpq-dev libcap2-bin \
    && install-php-extensions intl opcache pgsql pdo_pgsql zip \
    && setcap -r /usr/local/bin/frankenphp \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Installer les dépendances (pas de scripts pour l'instant)
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .

RUN cp .env.example .env

# Optimisations de production Symfony
RUN composer dump-autoload --optimize --no-dev \
    && php bin/console cache:clear --env=prod --no-interaction \
    && php bin/console assets:install public --env=prod --no-interaction

# OPCache actif en production (y compris en ligne de commande pour les workers).
RUN printf "opcache.enable=1\nopcache.enable_cli=1\nopcache.max_accelerated_files=20000\n" > /usr/local/etc/php/conf.d/zz-opcache.ini

# Copie de la configuration FrankenPHP / Caddy
COPY Caddyfile /app/Caddyfile

# Port d'écoute par défaut (Render injecte $PORT, sinon 8080).
EXPOSE 8080

# Migrations + seed (si base vide) puis lancement du serveur de production FrankenPHP.
CMD ["sh", "-c", "php bin/console doctrine:migrations migrate --no-interaction --env=prod 2>/dev/null; php bin/console doctrine:schema:update --force --env=prod --no-interaction 2>/dev/null; USER_COUNT=$(php bin/console dbal:run-sql \"SELECT COUNT(*) FROM \\\"user\\\"\" --env=prod --no-interaction 2>/dev/null | tr -d '[:space:]'); if [ \"$USER_COUNT\" = \"0\" ] || [ -z \"$USER_COUNT\" ]; then echo 'Empty DB - seeding demo data...'; php bin/console app:db:reset-demo --env=prod --no-interaction 2>&1; fi; frankenphp run --config /app/Caddyfile"]
