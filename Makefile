compose=docker compose

dc:
	@${compose} -f docker-compose.yml $(cmd)

dcr:
	@make dc cmd="run --rm php-cli $(cmd)"

stop:
	@make dc cmd="stop"

up:
	@make dc cmd="up -d"

build-flowbite:
	npm run flowbite:build

build-containers:
	@make dc cmd="up -d --build"

down:
	@make dc cmd="down"

console:
	@make dcr cmd="bin/console $(arg)"

migrate-diff:
	@make console arg="doctrine:migrations:diff"

migrate-run:
	@make console arg="doctrine:migrations:migrate"

composer:
	@make dcr cmd="composer $(arg)"

# Code quality tools.
phpunit:
	@make dcr cmd="vendor/bin/phpunit -d --enable-pretty-print -d --compact $(arg)"

phpstan:
	@make dcr cmd="vendor/bin/phpstan --memory-limit=1G $(arg)"

csfix:
	@make dcr cmd="vendor/bin/php-cs-fixer fix --config=.php-cs-fixer.dist.php"

# Helpers to manage app imports.
app-import-data:
	docker compose exec app bin/console app:strava:import-data

app-build-files:
	docker compose exec app bin/console app:strava:build-files

app-build-flowbite:
	npx tailwindcss -i public/assets/flowbite/tailwind.css -o public/assets/flowbite/tailwind.min.css

app-build-all:
	@make build-containers
	@make app-build-files
	@make app-build-flowbite
	@make build-containers