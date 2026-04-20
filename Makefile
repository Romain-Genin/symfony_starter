.DEFAULT_GOAL := help
.PHONY: help install start stop restart build bash cc migration migrate assets assets-dev assets-watch logs db-shell test vapid-keys create-admin routes entities

DOCKER_COMPOSE   = docker compose
PHP              = $(DOCKER_COMPOSE) exec php
COMPOSER_FLAGS   = --no-interaction --prefer-dist

_clean_flex = rm -f docker-compose.override.yml docker-compose.override.yaml \
              compose.override.yml compose.override.yaml compose.yaml compose.yml

##
## ── SETUP ────────────────────────────────────────────────────
##

install: ## Premier lancement complet
	$(call _clean_flex)
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d
	@echo "→ Génération du squelette Symfony via create-project..."
	$(DOCKER_COMPOSE) exec -e COMPOSER_NO_INTERACTION=1 php bash -c " \
		composer create-project symfony/skeleton:'^7.0' /tmp/sf $(COMPOSER_FLAGS) --quiet && \
		rsync -a --ignore-existing /tmp/sf/ /var/www/html/ && \
		rm -rf /tmp/sf"
	@echo "→ Suppression des configs qui entreraient en conflit avec Flex..."
	$(PHP) bash -c "rm -f config/packages/security.yaml config/packages/twig.yaml config/packages/mailer.yaml config/packages/datatables.yaml"
	@echo "→ Installation des packages..."
	$(DOCKER_COMPOSE) exec -e COMPOSER_NO_INTERACTION=1 php composer require $(COMPOSER_FLAGS) --no-scripts \
		symfony/asset \
		symfony/form \
		symfony/http-client \
		symfony/mailer \
		symfony/password-hasher \
		symfony/property-access \
		symfony/security-bundle \
		symfony/twig-bundle \
		symfony/validator \
		symfony/webpack-encore-bundle \
		twig/extra-bundle \
		"doctrine/doctrine-bundle:^2.12" \
		"doctrine/doctrine-migrations-bundle:^3.3" \
		"doctrine/orm:^3.2" \
		"dompdf/dompdf:^2.0" \
		"minishlink/web-push:^8.0" \
		"omines/datatables-bundle:^0.8"
	$(DOCKER_COMPOSE) exec -e COMPOSER_NO_INTERACTION=1 php composer require $(COMPOSER_FLAGS) --no-scripts --dev \
		symfony/browser-kit \
		symfony/css-selector \
		symfony/debug-bundle \
		symfony/maker-bundle \
		symfony/stopwatch \
		symfony/web-profiler-bundle
	$(call _clean_flex)
	@echo "→ Restauration des configs personnalisées depuis git..."
	$(PHP) git checkout -- config/packages/security.yaml config/packages/twig.yaml config/packages/mailer.yaml config/packages/datatables.yaml config/services.yaml
	$(PHP) php bin/console cache:clear
	@echo "→ Installation des dépendances JS..."
	$(PHP) npm install
	@echo "→ Migrations..."
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction
	@echo "→ Build des assets..."
	$(PHP) npm run dev
	@echo ""
	@echo "✔  Installation terminée !"
	@echo "   App     → http://localhost:$$(grep ^NGINX_PORT .env | cut -d= -f2)"
	@echo "   Mailpit → http://localhost:$$(grep ^MAILPIT_UI_PORT .env | cut -d= -f2)"
	@echo ""

##
## ── DOCKER ───────────────────────────────────────────────────
##

start: ## Démarrer les conteneurs
	$(DOCKER_COMPOSE) up -d

stop: ## Arrêter les conteneurs
	$(DOCKER_COMPOSE) down

restart: ## Redémarrer les conteneurs
	$(DOCKER_COMPOSE) down && $(DOCKER_COMPOSE) up -d

build: ## Rebuild les images Docker
	$(DOCKER_COMPOSE) build

##
## ── DÉVELOPPEMENT ────────────────────────────────────────────
##

bash: ## Shell dans le conteneur PHP
	$(PHP) bash

cc: ## Vider le cache Symfony
	$(PHP) php bin/console cache:clear

##
## ── BASE DE DONNÉES ──────────────────────────────────────────
##

migration: ## Créer une migration Doctrine
	$(PHP) php bin/console make:migration

migrate: ## Jouer les migrations en attente
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction

db-shell: ## Shell MySQL
	$(DOCKER_COMPOSE) exec mariadb mariadb -u symfony -psymfony symfony

##
## ── ASSETS ───────────────────────────────────────────────────
##

assets: ## Build assets (production)
	$(PHP) npm run build

assets-dev: ## Build assets (développement)
	$(PHP) npm run dev

assets-watch: ## Watch & rebuild automatiquement
	$(PHP) npm run watch

##
## ── SYMFONY ──────────────────────────────────────────────────
##

create-admin: ## Créer un compte administrateur (interactif)
	$(PHP) php bin/console app:create-admin

vapid-keys: ## Générer les clés VAPID (push notifications)
	$(PHP) php bin/console app:generate-vapid-keys

routes: ## Lister les routes
	$(PHP) php bin/console debug:router

entities: ## Lister les entités Doctrine
	$(PHP) php bin/console debug:doctrine:mapping

##
## ── LOGS & TESTS ─────────────────────────────────────────────
##

logs: ## Suivre les logs des conteneurs
	$(DOCKER_COMPOSE) logs -f

test: ## Lancer PHPUnit
	$(PHP) php bin/phpunit

##
## ────────────────────────────────────────────────────────────
##

help:
	@grep -E '(^[a-zA-Z_-]+:.*?## .*)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; /^##/ {print "\033[33m"$$0"\033[0m"} /^[^#]/ {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
