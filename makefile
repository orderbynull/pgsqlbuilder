#!/usr/bin/make
# Makefile readme (ru): <http://linux.yaroslavl.ru/docs/prog/gnu_make_3-79_russian_manual.html>

test:
	clear && DB_HOST=127.0.0.1 DB_PORT=5432 DB_USERNAME=pgsql DB_PASSWORD=pgsql php vendor/bin/phpunit --stop-on-error
