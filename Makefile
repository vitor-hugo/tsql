help:
	@echo "Please use 'make <target>' where <target> is one of"
	@echo "  start-server   to start docker development server"
	@echo "  stop-server    to stop docker development server"
	@echo "  test           to perform the tests"
	@echo "  dox            to perform the tests with testdox"
	@echo "  coverage       to start coverage html report server (http://localhost:8000)"
	@echo "  gen-doc        to run PHPDocumentation generator"
	@echo "  doc-server     to start documentation server (http://localhost:8001)"

start-server:
	@docker compose up -d
	@sleep 3
	@docker exec tsql-mssql bash -c "/opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P SuperStrongPassword! -i /tmp/create-database.sql"
	@docker exec tsql-mssql bash -c "/opt/mssql-tools/bin/sqlcmd -S localhost -U sa -P SuperStrongPassword! -i /tmp/create-table.sql"


stop-server:
	@docker compose stop


test:
ifeq ($(shell docker ps --format '{{.Names}}' | grep tsql-mssql), tsql-mssql)
	clear
	@vendor/bin/phpunit --display-errors --display-warnings --display-deprecations
else
	@echo "Test server not running, please run 'make start-server'"
endif


dox:
ifeq ($(shell docker ps --format '{{.Names}}' | grep tsql-mssql), tsql-mssql)
	clear
	@vendor/bin/phpunit --no-progress --testdox --display-errors --display-warnings --display-deprecations
else
	@echo "Test server not running, please run 'make start-server'"
endif


REPORT_PATH := $(firstword $(wildcard ./tests/report/html/index.html))
coverage:
ifeq (,$(REPORT_PATH))
	@echo "HTML report not found. Please, run 'make test' to generate the coverage page."
else
	@php -S localhost\:8000 -t tests/report/html/
endif


gen-doc:
	docker run --rm -v $(shell pwd):/data phpdoc/phpdoc:3 -d ./src -t ./docs


DOC_PATH := $(firstword $(wildcard ./docs/index.html))
doc-server:
ifeq (,$(DOC_PATH))
	@echo "Documentation folder not found."
else
	@php -S localhost\:8001 -t docs/
endif


clear:
	@rm -rf docs && rm -rf .phpdoc && rm -rf tests/cache && rm -rf tests/report
