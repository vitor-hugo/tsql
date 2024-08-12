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
	clear
	@echo "Initializing database servers, this operation may take a while..."
	@docker compose up -d
	@sleep 20
	@$(MAKE) init-db


init-db:
	@docker compose exec tsql-mssql bash -c "/opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P SuperStrongPassword! -i /tmp/sqlserver/create-database.sql -C"
	@docker compose exec tsql-mssql bash -c "/opt/mssql-tools18/bin/sqlcmd -S localhost -U sa -P SuperStrongPassword! -i /tmp/sqlserver/create-table.sql -C"
	@docker compose exec tsql-mysql bash -c "mysql -e \"source /tmp/mysql/create-table.sql\"  -usuper -p12345"
	@docker compose exec tsql-mariadb bash -c "mariadb -e \"source /tmp/mysql/create-table.sql\"  -uroot -p12345"
	@docker compose exec tsql-postgres bash -c "psql -U super -d TestDB -a -f /tmp/postgres/create-table.sql"

stop-server:
	@docker compose stop

down-server:
	@docker compose down -v

start: start-server
stop: stop-server
down: down-server

test:
ifneq ($(shell docker ps --format '{{.Names}}' | grep tsql-mssql), tsql-mssql)
	@$(MAKE) start-server
endif
	clear
	@vendor/bin/phpunit --display-errors --display-warnings --display-deprecations


dox:
ifneq ($(shell docker ps --format '{{.Names}}' | grep tsql-mssql), tsql-mssql)
	@$(MAKE) start-server
endif
	clear
	@vendor/bin/phpunit --no-progress --testdox --display-errors --display-warnings --display-deprecations


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
