# DevFlow — Developer convenience commands
# Usage: make <target>

.PHONY: up down restart build logs shell migrate seed test lint fresh

# ── Docker ────────────────────────────────────────────────────────
up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

build:
	docker-compose build --no-cache

logs:
	docker-compose logs -f

logs-backend:
	docker-compose logs -f backend

logs-reverb:
	docker-compose logs -f reverb

logs-queue:
	docker-compose logs -f queue

# ── Backend ───────────────────────────────────────────────────────
shell:
	docker-compose exec backend sh

migrate:
	docker-compose exec backend php artisan migrate

migrate-replica:
	docker-compose exec backend php artisan migrate --database=mysql_replica

seed:
	docker-compose exec backend php artisan db:seed

fresh:
	docker-compose exec backend php artisan migrate:fresh --seed

test:
	docker-compose exec backend php artisan test --parallel

test-filter:
	docker-compose exec backend php artisan test --filter=$(filter)

tinker:
	docker-compose exec backend php artisan tinker

cache-clear:
	docker-compose exec backend php artisan cache:clear
	docker-compose exec backend php artisan config:clear
	docker-compose exec backend php artisan route:clear

queue-restart:
	docker-compose exec backend php artisan queue:restart

# ── Frontend ──────────────────────────────────────────────────────
frontend-shell:
	docker-compose exec frontend sh

frontend-install:
	docker-compose exec frontend npm install

# ── Redis ─────────────────────────────────────────────────────────
redis-cli:
	docker-compose exec redis redis-cli

redis-flush:
	docker-compose exec redis redis-cli flushall

# ── Database ──────────────────────────────────────────────────────
mysql-cli:
	docker-compose exec mysql mysql -u root -proot devflow

mysql-replica-cli:
	docker-compose exec mysql_replica mysql -u root -proot devflow

# ── Health ────────────────────────────────────────────────────────
health:
	curl -s http://localhost:8000/api/health | python3 -m json.tool

# ── Git ───────────────────────────────────────────────────────────
status:
	git status

# ── Help ──────────────────────────────────────────────────────────
help:
	@echo ""
	@echo "DevFlow available commands:"
	@echo ""
	@echo "  make up                 Start all containers"
	@echo "  make down               Stop all containers"
	@echo "  make restart            Restart all containers"
	@echo "  make build              Rebuild all images"
	@echo "  make logs               Tail all container logs"
	@echo "  make logs-backend       Tail backend logs only"
	@echo "  make shell              Open shell in backend container"
	@echo "  make migrate            Run database migrations"
	@echo "  make migrate-replica    Run migrations on read replica"
	@echo "  make seed               Run database seeders"
	@echo "  make fresh              Fresh migrate with seed"
	@echo "  make test               Run all Pest tests in parallel"
	@echo "  make test-filter filter=TaskTest  Run specific test"
	@echo "  make tinker             Open Laravel Tinker"
	@echo "  make cache-clear        Clear all Laravel caches"
	@echo "  make redis-cli          Open Redis CLI"
	@echo "  make redis-flush        Flush all Redis data"
	@echo "  make mysql-cli          Open MySQL CLI (primary)"
	@echo "  make mysql-replica-cli  Open MySQL CLI (replica)"
	@echo "  make health             Check API health endpoint"
	@echo ""