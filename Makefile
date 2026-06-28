.PHONY: help cache optimize migrate deploy logs restart shell

# Use Compose v2 ("docker compose") when available, else fall back to the v1
# binary ("docker-compose"). The NAS (Synology Container Manager) only ships v1.
COMPOSE := $(shell docker compose version >/dev/null 2>&1 && echo "docker compose" || echo "docker-compose")

help: ## Toon beschikbare commando's
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

cache: ## Warm alle Laravel caches op (config, route, view, event)
	$(COMPOSE) exec hub php artisan config:cache
	$(COMPOSE) exec hub php artisan route:cache
	$(COMPOSE) exec hub php artisan view:cache
	$(COMPOSE) exec hub php artisan event:cache

optimize: ## Optimaliseer autoloader + Laravel caches (na git pull)
	$(COMPOSE) exec hub composer install --no-dev --optimize-autoloader --no-interaction
	$(COMPOSE) exec hub php artisan optimize

migrate: ## Draai database-migraties in de container (geforceerd, productie)
	$(COMPOSE) exec hub sh -c "php artisan migrate --force"

deploy: ## Volledige deploy na 'git pull' (optimize + migrate + caches + restart)
	$(MAKE) optimize
	$(MAKE) migrate
	$(MAKE) cache
	$(MAKE) restart

logs: ## Volg container logs
	$(COMPOSE) logs -f hub

restart: ## Herstart de hub container
	$(COMPOSE) restart hub

shell: ## Open een shell in de container
	$(COMPOSE) exec hub sh
