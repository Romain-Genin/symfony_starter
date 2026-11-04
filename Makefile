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
	@echo "→ Installation des dépendances PHP..."
	$(DOCKER_COMPOSE) exec -e COMPOSER_NO_INTERACTION=1 php composer install $(COMPOSER_FLAGS)
	@echo "→ Installation des dépendances JS..."
	$(PHP) npm install
	@echo "→ Migrations..."
	$(PHP) php bin/console doctrine:migrations:diff --no-interaction || true
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
