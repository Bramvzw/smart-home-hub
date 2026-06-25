.PHONY: help cache optimize deploy logs restart shell

help: ## Toon beschikbare commando's
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-10s\033[0m %s\n", $$1, $$2}'

cache: ## Warm alle Laravel caches op (config, route, view, event)
	docker compose exec hub php artisan config:cache
	docker compose exec hub php artisan route:cache
	docker compose exec hub php artisan view:cache
	docker compose exec hub php artisan event:cache

optimize: ## Optimaliseer autoloader + Laravel caches (na git pull)
	docker compose exec hub composer install --no-dev --optimize-autoloader --no-interaction
	docker compose exec hub php artisan optimize

deploy: ## Volledige deploy na 'git pull' (optimize + caches + restart)
	$(MAKE) optimize
	$(MAKE) cache
	$(MAKE) restart

logs: ## Volg container logs
	docker compose logs -f hub

restart: ## Herstart de hub container
	docker compose restart hub

shell: ## Open een shell in de container
	docker compose exec hub sh
