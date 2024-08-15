CURRENT_USER    = $(shell id -u):$(shell id -g)
USER_NAME       = $(shell whoami)

DOCKER_COMPOSE  = CURRENT_USER=$(CURRENT_USER) docker compose

EXEC_PHP        = $(DOCKER_COMPOSE) exec php
EXEC_SUDO_PHP   = $(DOCKER_COMPOSE) exec -u root php

##
## Project
## -------
##

build:
	$(DOCKER_COMPOSE) build --pull

kill:
	$(DOCKER_COMPOSE) kill
	$(DOCKER_COMPOSE) down --volumes --remove-orphans

install: ## Install and start the project
install: build start useradd vendor

reset: ## Stop and start a fresh install of the project
reset: kill install

docker-sync-start: #start docker sync
	docker-sync start

start: ## Start the project
	$(DOCKER_COMPOSE) up -d --remove-orphans --no-recreate

useradd: ## Add your host user to the container
	$(EXEC_SUDO_PHP) groupadd -f -g $(shell id -g) $(USER_NAME)
	$(EXEC_SUDO_PHP) useradd -u $(shell id -u) -g $(shell id -g) -m $(USER_NAME)

stop: ## Stop the project
	$(DOCKER_COMPOSE) stop

enter: ## Login into php docker
	$(EXEC_PHP) bash

clear-cache:
	$(EXEC_SUDO_PHP) bash -c 'rm -rf var/cache/*'

vendor:
	$(EXEC_PHP) composer install --prefer-dist

test:
	$(EXEC_PHP) php ./bin/phpunit
