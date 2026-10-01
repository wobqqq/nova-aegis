SHELL := /bin/bash

export UID := $(shell id -u)
export GID := $(shell id -g)

PHP := docker compose run --rm php
NODE := docker compose run --rm node

.PHONY: docker.build install update shell \
	composer.code.fix composer.code.check composer.code.stan composer.test composer.test.coverage composer.test.mutate \
	npm.install npm.code.fix npm.code.check npm.test npm.test.coverage npm.build \
	code.fix code.check test test.coverage test.mutate ready

# ─────────────────────────────── Docker ───────────────────────────────
docker.build:
	docker compose build

shell:
	$(PHP) sh

# ────────────────────────────── Composer ──────────────────────────────
install: npm.install
	$(PHP) composer install

update:
	$(PHP) composer update

composer.code.fix:
	$(PHP) composer code.fix

composer.code.check:
	$(PHP) composer code.check

composer.code.stan:
	$(PHP) composer code.stan

composer.test:
	$(PHP) composer test

composer.test.coverage:
	$(PHP) composer test.coverage

composer.test.mutate:
	$(PHP) composer test.mutate

# ──────────────────────────────── NPM ─────────────────────────────────
npm.install:
	$(NODE) npm ci

npm.code.fix:
	$(NODE) npm run code.fix

npm.code.check:
	$(NODE) npm run code.check

npm.test:
	$(NODE) npm run test

npm.test.coverage:
	$(NODE) npm run test.coverage

npm.build:
	$(NODE) npm run build

# ─────────────────────── Aggregates (composer + npm) ───────────────────
code.fix: composer.code.fix npm.code.fix

code.check: composer.code.check npm.code.check

test: composer.test npm.test

test.coverage: composer.test.coverage npm.test.coverage

test.mutate: composer.test.mutate

ready: code.fix code.check test.coverage npm.build
