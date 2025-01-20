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

vendor-prod: composer.json composer.lock
	@$(PHP) composer install -o -n --no-ansi --no-dev

db-create: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console doctrine:database:create

db-migrate: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console doctrine:migrations:migrate -n

twig-lint: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console lint:twig templates/

yaml-lint: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/console lint:yaml config/

FIXER_CMD=vendor/bin/php-cs-fixer fix

php-cs: $(PHP_CONSOLE_DEPS)
	@$(PHP) $(FIXER_CMD) --verbose
php-cs-gitlab:
	@$(PHP) $(FIXER_CMD) --dry-run --using-cache=no --show-progress=none -v

PHPSTAN_CMD=vendor/bin/phpstan analyse src tests

phpstan: $(PHP_CONSOLE_DEPS)
	@$(PHP) $(PHPSTAN_CMD)
phpstan-gitlab:
	@$(PHP) $(PHPSTAN_CMD) --no-progress

RECTOR_CMD=vendor/bin/rector process

rector: $(PHP_CONSOLE_DEPS)
	@$(PHP) $(RECTOR_CMD)
rector-gitlab:
	@$(PHP) $(RECTOR_CMD) --dry-run --no-progress-bar

check: php-cs phpstan rector

phpunit: $(PHP_CONSOLE_DEPS)
	@$(PHP) bin/phpunit

node-modules:
	@cd embed && make node-modules

js-build:
	@cd embed && make build

js-build-dev:
	@cd embed && make build-dev

js-lint:
	@cd embed && make lint

zip-archive: node-modules js-build
	@read -p "Enter new version (integer): " VERSION; \
	$(PHP) bin/console app:embed:zip $$VERSION
