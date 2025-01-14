YARN=docker compose run --rm --no-deps node yarn

node-modules: package.json yarn.lock ## установить js зависимости
	$(YARN) install
	@touch node_modules || true

build: node_modules ## собрать js (prod режим)
	$(YARN) build

build-dev: node_modules ## собрать js (prod режим)
	$(YARN) build:dev

lint: node_modules ## собрать js (prod режим)
	$(YARN) lint

lint-fix: node_modules ## собрать js (prod режим)
	$(YARN) lint:fix
