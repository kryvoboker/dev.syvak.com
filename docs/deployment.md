[← Testing](testing.md) · [Back to README](../../../README.md)

# Deployment

## Docker Compose Files

- Dev: `.docker/dev/docker-compose.yml`
- Prod: `.docker/prod/docker-compose.yml`
- Slim prod: `.docker/prod/docker-compose.slim.yml`

## Makefile Helpers

```bash
make up-dev
make down-dev
make build-dev

make up-prod
make down-prod
make build-prod
```

## Build Steps (Application)

```bash
cd app-code/app
composer install --no-dev --optimize-autoloader
npm ci
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
```

## Production Checklist

- Correct `.env` values for database/cache/queues/mail.
- Successful DB migrations.
- Built frontend assets (`npm run build`).
- App health checks and logs are monitored.
- Rollback strategy is prepared before deploy.

## See Also

- [Configuration](configuration.md) — env and config strategy.
- [Testing](testing.md) — pre-deploy verification commands.
- [Getting Started](getting-started.md) — local baseline setup.
