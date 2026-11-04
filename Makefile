# ---------------------------------------------------------------------------
# Commandes du projet (Docker local : compose.yaml)
# `make` ou `make help` pour la liste.
# ---------------------------------------------------------------------------
DC       = docker compose
PHP      = $(DC) exec php
CONSOLE  = $(PHP) php bin/console
COMPOSER = $(PHP) composer

export UID := $(shell id -u)
export GID := $(shell id -g)

.DEFAULT_GOAL := help
.PHONY: help install build up down restart logs sh composer console cc \
        tailwind tailwind-build assets migration migrate fixtures admin encryption-key \
        test db-test cs cs-fix phpstan qa purge-contacts

help: ## Affiche cette aide
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-16s\033[0m %s\n", $$1, $$2}'

## —— Docker ————————————————————————————————————————————————————————————————
install: build up ## Installation complète (build, dépendances, assets, base, fixtures)
	$(COMPOSER) install
	$(CONSOLE) importmap:install
	$(CONSOLE) tailwind:build
	$(CONSOLE) doctrine:migrations:migrate -n
	$(CONSOLE) doctrine:fixtures:load -n
	@echo "Site : http://localhost:8080 | Admin : admin@example.com / password-de-test-123 | Mails : http://localhost:8025"

build: ## Construit les images
	$(DC) build

up: ## Démarre les conteneurs
	$(DC) up -d --remove-orphans

down: ## Arrête les conteneurs
	$(DC) down

restart: down up ## Redémarre les conteneurs

logs: ## Logs en continu
	$(DC) logs -f

sh: ## Shell dans le conteneur PHP
	$(PHP) bash

## —— Symfony ———————————————————————————————————————————————————————————————
composer: ## Composer, ex : make composer c="require symfony/messenger"
	$(COMPOSER) $(c)

console: ## Console Symfony, ex : make console c="debug:router"
	$(CONSOLE) $(c)

cc: ## Vide le cache
	$(CONSOLE) cache:clear

## —— Assets ————————————————————————————————————————————————————————————————
tailwind: ## Compile Tailwind en continu (watch)
	$(CONSOLE) tailwind:build --watch

tailwind-build: ## Compile Tailwind une fois
	$(CONSOLE) tailwind:build

assets: ## Réinstalle les dépendances JS de l'importmap
	$(CONSOLE) importmap:install

## —— Base de données ———————————————————————————————————————————————————————
migration: ## Génère une migration à partir des entités
	$(CONSOLE) make:migration

migrate: ## Applique les migrations
	$(CONSOLE) doctrine:migrations:migrate -n

fixtures: ## Recharge les données de démo
	$(CONSOLE) doctrine:fixtures:load -n

admin: ## Crée un administrateur, ex : make admin email=moi@exemple.fr
	$(CONSOLE) app:user:create $(email) --admin

encryption-key: ## Génère une clé APP_ENCRYPTION_KEY
	$(CONSOLE) app:encryption:generate-key

purge-contacts: ## Purge RGPD des anciens messages de contact
	$(CONSOLE) app:contact:purge

## —— Qualité ———————————————————————————————————————————————————————————————
db-test: ## (Re)crée la base de test
	$(CONSOLE) doctrine:database:drop --env=test --force --if-exists
	$(CONSOLE) doctrine:database:create --env=test

test: ## Lance les tests PHPUnit
	$(PHP) php bin/phpunit

cs: ## Vérifie le style de code
	$(PHP) vendor/bin/php-cs-fixer fix --dry-run --diff

cs-fix: ## Corrige le style de code
	$(PHP) vendor/bin/php-cs-fixer fix

phpstan: ## Analyse statique
	$(CONSOLE) cache:warmup
	$(PHP) vendor/bin/phpstan analyse --memory-limit=1G

qa: cs phpstan test ## Toutes les vérifications
