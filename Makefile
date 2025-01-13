PHP=docker compose run --rm --no-deps php
ROOT_DIR:=$(shell dirname $(realpath $(lastword $(MAKEFILE_LIST))))

PHP_CONSOLE_DEPS=vendor .env

.PHONY: start
start: up $(PHP_CONSOLE_DEPS) db-migrate

up: docker-compose.yml ## запустить сервис
	@docker compose up -d --build --remove-orphans --quiet-pull

stop: docker-compose.yml ## остановить сервис
	@docker compose stop

vendor: composer.json composer.lock
	@$(PHP) composer install -o -n --no-ansi
	@touch vendor || true

db-create: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console doctrine:database:create

db-migrate: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console doctrine:migrations:migrate -n

twig-lint: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console lint:twig templates/

yaml-lint: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console lint:yaml config/

FIXER_CMD=vendor/bin/php-cs-fixer fix

fixer: $(PHP_CONSOLE_DEPS)
	@$(PHP) $(FIXER_CMD) --verbose
fixer-gitlab:
	@$(PHP) $(FIXER_CMD) --dry-run --using-cache=no --show-progress=none -v

PHPSTAN_CMD=vendor/bin/phpstan analyse src tests

phpstan: $(PHP_CONSOLE_DEPS)
	@$(PHP) $(PHPSTAN_CMD)
phpstan-gitlab:
	@$(PHP) $(PHPSTAN_CMD) --no-progress

RECTOR_CMD=vendor/bin/rector process

rector: $(PHP_CONSOLE_DEPS)
	@$(PHP) $(RECTOR_CMD)

check: fixer phpstan rector

phpunit: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/phpunit

JS_PACKAGE_MANAGER=docker compose run --rm --no-deps node yarn
JS_BUILD_DEPS=$(PHP_CONSOLE_DEPS) node_modules

node_modules: package.json yarn.lock ## установить js зависимости
	$(JS_PACKAGE_MANAGER) install
	@touch node_modules || true

js-build-dev: $(JS_BUILD_DEPS) ## собрать js (dev режим)
	$(JS_PACKAGE_MANAGER) dev

js-build-prod: $(JS_BUILD_DEPS) ## собрать js (prod режим)
	$(JS_PACKAGE_MANAGER) build
