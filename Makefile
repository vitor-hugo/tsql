help:
	@echo "Please use 'make <target>' where <target> is one of"
	@echo "  start-server  to start docker development server"
	@echo "  stop-server   to stop docker development server"
	@echo "  test          to perform the tests"
	@echo "  testdox       to perform the tests with testdox"

start-server:
	@docker compose up -d
	@sleep 10
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
