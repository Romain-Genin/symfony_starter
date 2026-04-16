.DEFAULT_GOAL := help
.PHONY: help install start stop restart build bash cc migration migrate assets assets-watch logs db-shell test

DOCKER_COMPOSE = docker compose
PHP = $(DOCKER_COMPOSE) exec php

##
## ── SETUP ────────────────────────────────────────────────────
##

install: ## Premier lancement : build + composer + npm + migrations
	$(DOCKER_COMPOSE) build --no-cache
	$(DOCKER_COMPOSE) up -d
	$(PHP) composer install
	$(PHP) npm install
	$(PHP) php bin/console doctrine:migrations:migrate --no-interaction
	$(PHP) npm run dev
	@echo ""
	@echo "✔  Installation terminée !"
	@echo "   App       → http://localhost:$$(grep NGINX_PORT .env | cut -d= -f2 | tr -d ' ')"
	@echo "   Mailpit   → http://localhost:$$(grep MAILPIT_UI_PORT .env | cut -d= -f2 | tr -d ' ')"
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

migration: ## Créer une nouvelle migration Doctrine
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

assets-watch: ## Watch & rebuild les assets automatiquement
	$(PHP) npm run watch

##
## ── SYMFONY ──────────────────────────────────────────────────
##

routes: ## Lister les routes
	$(PHP) php bin/console debug:router

entities: ## Lister les entités Doctrine
	$(PHP) php bin/console debug:doctrine:mapping

##
## ── LOGS & TESTS ─────────────────────────────────────────────
##

logs: ## Suivre les logs des conteneurs
	$(DOCKER_COMPOSE) logs -f

test: ## Lancer les tests PHPUnit
	$(PHP) php bin/phpunit

##
## ────────────────────────────────────────────────────────────
##

help: ## Afficher cette aide
	@grep -E '(^[a-zA-Z_-]+:.*?## .*)|(^##)' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; /^##/ {print "\033[33m"$$0"\033[0m"} /^[^#]/ {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'
