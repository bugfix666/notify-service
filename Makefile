.DEFAULT_GOAL := help

SHELL = /bin/sh

export DOCKER_BUILDKIT=0
export COMPOSE_DOCKER_CLI_BUILD=0

USER_ID=$(shell id -u)
GROUP_ID=$(shell id -g)

export USER_ID
export GROUP_ID

up: ## Initialize, install & run docker
	make restart \
	&& make install

restart: ## Drop & restart docker
	make stop && \
	make build && \
	make start

start: ## Start docker
	if [ -d /var/run/docker.sock ];then \
	sudo chown ${USER} /var/run/docker.sock ;\
	fi
	if [ -d /run/docker.sock ];then \
	sudo chown ${USER} /run/docker.sock ;\
	fi
	docker compose up -d --remove-orphans

stop: ## Stop docker
	docker compose down

openapi: ## Build openapi documentation
	docker exec -i app composer openapi

migrate: ## Drop all tables and re-run all migrations
	docker exec -i app php artisan migrate:fresh --seed --force

buildapp:
	docker exec -i app rm -rf bootstrap/cache/*.php
	docker exec -i app git config --global --add safe.directory /www
 	# docker exec -i app pecl install opentelemetry && echo 'extension=opentelemetry.so' > /etc/php/8.3/mods-available/opentelemetry.ini && phpenmod -s ALL opentelemetry
	docker exec -i app composer install
	docker exec -i app php artisan key:generate --force
	#docker exec -i app php artisan optimize
	#docker exec -i app php artisan optimize:clear

test: ## Run tests
	docker exec -i app composer tests

route: ## Generate routes & autoload files
	docker exec -i app composer dump-autoload
	docker exec -i app php artisan optimize:clear

build:
	docker compose build

env:
	if ! [ -f .env ];then cp .env.example .env;fi

cs-check: ## Code style check
	docker exec -i app composer cs-check

phpstan: ## Check PHP standards
	docker exec -i app composer phpstan

lint: ## Run PHP linter
	docker exec -i app composer lint

psalm: ## Run PSALM
	docker exec -i app composer psalm

validate-composer: ## Run composer validation
	docker exec -i app composer validate --strict

rector: ## Run rector
	docker exec -i app composer rector

rector-fix: ## Run fix rector
	docker exec -i app composer rector-fix

install:
	make env && \
	make buildapp && \
	sleep 10 && \
	make migrate

ide-helper: ## Make ide-helper files
	docker exec -i app php artisan ide-helper:generate
	docker exec -i app php artisan ide-helper:models -M
	docker exec -i app php artisan ide-helper:meta

.PHONY: help

help:
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'
