.PHONY: help build up down logs shell composer install migrate db-create db-drop cache-clear test lint format clean

# Colors for output
BLUE=\033[0;34m
GREEN=\033[0;32m
RED=\033[0;31m
YELLOW=\033[1;33m
NC=\033[0m # No Color

help: ## Display this help message
	@echo "$(BLUE)╔════════════════════════════════════════════════════════════╗$(NC)"
	@echo "$(BLUE)║          RH Analyser - Development Commands              ║$(NC)"
	@echo "$(BLUE)╚════════════════════════════════════════════════════════════╝$(NC)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "$(GREEN)%-20s$(NC) %s\n", $$1, $$2}'
	@echo ""
	@echo "$(YELLOW)Usage:$(NC) make [target]"
	@echo ""

# Docker Commands
build: ## Build Docker containers
	@echo "$(BLUE)Building containers...$(NC)"
	docker-compose build

up: ## Start Docker containers
	@echo "$(BLUE)Starting containers...$(NC)"
	docker-compose up -d
	@echo "$(GREEN)✓ Services running$(NC)"
	@echo "  - Frontend: http://localhost:8080"
	@echo "  - API Health: http://localhost:8080/api/health"

down: ## Stop Docker containers
	@echo "$(BLUE)Stopping containers...$(NC)"
	docker-compose down
	@echo "$(GREEN)✓ Services stopped$(NC)"

restart: ## Restart Docker containers
	@echo "$(BLUE)Restarting containers...$(NC)"
	docker-compose restart
	@echo "$(GREEN)✓ Services restarted$(NC)"

logs: ## View Docker logs (follow mode)
	docker-compose logs -f

logs-php: ## View PHP logs
	docker-compose logs -f php

logs-nginx: ## View Nginx logs
	docker-compose logs -f nginx

logs-db: ## View Database logs
	docker-compose logs -f database

ps: ## List running containers
	docker-compose ps

# PHP Commands
shell: ## Open PHP shell
	docker-compose exec php bash

composer-install: ## Install PHP dependencies
	docker-compose exec php composer install

composer-update: ## Update PHP dependencies
	docker-compose exec php composer update

composer: ## Run composer command (usage: make composer ARGS="require package/name")
	docker-compose exec php composer $(ARGS)

# Database Commands
migrate: ## Run database migrations
	@echo "$(BLUE)Running migrations...$(NC)"
	docker-compose exec php php bin/console doctrine:migrations:migrate --no-interaction
	@echo "$(GREEN)✓ Migrations completed$(NC)"

db-create: ## Create the database
	@echo "$(BLUE)Creating database...$(NC)"
	docker-compose exec php php bin/console doctrine:database:create
	@echo "$(GREEN)✓ Database created$(NC)"

db-drop: ## Drop the database
	@echo "$(RED)Dropping database...$(NC)"
	docker-compose exec php php bin/console doctrine:database:drop --force
	@echo "$(GREEN)✓ Database dropped$(NC)"

db-reset: db-drop db-create migrate ## Reset database to clean state
	@echo "$(GREEN)✓ Database reset complete$(NC)"

# Cache Commands
cache-clear: ## Clear Symfony cache
	@echo "$(BLUE)Clearing cache...$(NC)"
	docker-compose exec php php bin/console cache:clear
	@echo "$(GREEN)✓ Cache cleared$(NC)"

cache-warmup: ## Warm up Symfony cache
	@echo "$(BLUE)Warming up cache...$(NC)"
	docker-compose exec php php bin/console cache:warmup
	@echo "$(GREEN)✓ Cache warmed$(NC)"

# Testing & Quality
test: ## Run PHPUnit tests
	@echo "$(BLUE)Running tests...$(NC)"
	docker-compose exec php php bin/phpunit

test-coverage: ## Run tests with coverage
	@echo "$(BLUE)Running tests with coverage...$(NC)"
	docker-compose exec php php bin/phpunit --coverage-html=coverage/
	@echo "$(GREEN)✓ Coverage report generated: coverage/index.html$(NC)"

lint: ## Run PHP linting
	@echo "$(BLUE)Linting PHP code...$(NC)"
	docker-compose exec php php vendor/bin/phpstan analyze src/
	@echo "$(GREEN)✓ Linting complete$(NC)"

format: ## Format PHP code with PHP-CS-Fixer
	@echo "$(BLUE)Formatting code...$(NC)"
	docker-compose exec php php vendor/bin/php-cs-fixer fix src/ --verbose
	@echo "$(GREEN)✓ Code formatted$(NC)"

# Gemini API Testing
test-gemini: ## Test Gemini API connection
	@echo "$(BLUE)Testing Gemini API...$(NC)"
	docker-compose exec php php bin/console app:test-gemini "Test job description" "Test CV"

# Setup & Installation
install: composer-install migrate ## Complete installation
	@echo "$(GREEN)✓ Installation complete$(NC)"

setup: build up install ## Full setup from scratch
	@echo "$(GREEN)✓ Setup complete!$(NC)"
	@echo "  Open: http://localhost:8080"

# Development Tools
tinker: ## Open Symfony tinker REPL
	docker-compose exec php php bin/console tinker

routes: ## Show all application routes
	docker-compose exec php php bin/console debug:router

config: ## Show Symfony configuration
	docker-compose exec php php bin/console debug:config

# Cleaning
clean: ## Remove generated files and cache
	@echo "$(BLUE)Cleaning up...$(NC)"
	docker-compose exec php rm -rf var/cache/* var/logs/*
	@echo "$(GREEN)✓ Cleanup complete$(NC)"

clean-all: down ## Remove everything including containers and volumes
	@echo "$(RED)WARNING: This will remove all containers and data!$(NC)"
	docker-compose down -v
	@echo "$(GREEN)✓ Everything cleaned$(NC)"

# Asset Management
assets-build: ## Build frontend assets
	@echo "$(BLUE)Building assets...$(NC)"
	docker-compose exec php php bin/console asset-map:compile
	@echo "$(GREEN)✓ Assets built$(NC)"

# Environment
env-show: ## Show current environment variables
	@grep -E '^[A-Z_]+=' .env | grep -v '^#'

env-validate: ## Validate environment configuration
	@echo "$(BLUE)Validating .env...$(NC)"
	@if [ -f .env ]; then \
		if grep -q "GEMINI_API_KEY=$$" .env; then \
			echo "$(RED)✗ GEMINI_API_KEY not set$(NC)"; \
		else \
			echo "$(GREEN)✓ GEMINI_API_KEY is set$(NC)"; \
		fi; \
	else \
		echo "$(RED)✗ .env file not found$(NC)"; \
	fi

# Docker System Cleanup
docker-clean: ## Clean Docker system (removes unused images/containers)
	@echo "$(BLUE)Cleaning Docker system...$(NC)"
	docker system prune -f
	@echo "$(GREEN)✓ Docker cleaned$(NC)"

# Monitoring
monitor: ## Monitor container stats in real-time
	docker stats

cpu: ## Show CPU usage
	docker stats --no-stream --format "table {{.Container}}\t{{.CPUPerc}}\t{{.MemUsage}}"

# Update/Upgrade
update-composer: ## Update all Composer packages
	docker-compose exec php composer update

upgrade: build up ## Rebuild and restart containers
	@echo "$(GREEN)✓ Upgrade complete$(NC)"

# Documentation
docs: ## Generate API documentation
	@echo "$(BLUE)Generating documentation...$(NC)"
	docker-compose exec php php bin/console debug:router > API_ROUTES.txt
	@echo "$(GREEN)✓ Documentation generated$(NC)"

.DEFAULT_GOAL := help
