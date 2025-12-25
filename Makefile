set-node:
	bash -c "source ~/.nvm/nvm.sh && nvm use 24.11.0"

up-dev: set-node
	docker compose -f .docker/dev/docker-compose.yml up -d

down-dev:
	docker compose -f .docker/dev/docker-compose.yml down

build-dev:
	docker compose -f .docker/dev/docker-compose.yml build

restart-dev: down-dev up-dev

up-prod: set-node
	docker compose -f .docker/prod/docker-compose.yml up -d

down-prod:
	docker compose -f .docker/prod/docker-compose.yml down

build-prod:
	docker compose -f .docker/prod/docker-compose.yml build

restart-prod: down-prod up-prod

vite:
	cd httpdocs/app && npm run dev

vite-build:
	cd httpdocs/app \
	&& npm run build

spfdb:
	chown -R ${id -u}:${id -g} db

# If after slim you get the error “missing shared library / extension”, add --include-path /usr/lib/x86_64-linux-gnu (often required for GD/ICU).

build-slim-prod:
	docker-slim build \
      --http-probe=false \
      --include-path /usr/bin/php \
      --include-path /usr/lib/php \
      --include-path /usr/local/lib/php \
      --include-path /usr/local/etc/php \
      --include-path /var/www/app \
      --include-path /var/www/html \
      --tag dev-syvak-app:prod \
      dev-syvak-app:latest