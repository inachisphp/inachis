.PHONY: help build-release check-update apply-update phpstan phpunit qa clean

# Color output helpers
CYAN := \033[36m
GREEN := \033[32m
YELLOW := \033[33m
RESET := \033[0m

help: ## Display available commands
	@echo "$(CYAN)Inachis Framework Management & Build Commands$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'

build-release: ## Build release zip package and JSON manifest (build/dist/)
	@echo "$(CYAN)Building release package...$(RESET)"
	@php bin/console inachis:build

check-update: ## Check GitHub for available core updates without installing
	@echo "$(CYAN)Checking for system updates...$(RESET)"
	@php bin/console inachis:system:update --check-only

apply-update: ## Run system updater interactively
	@echo "$(CYAN)Running interactive system update...$(RESET)"
	@php bin/console inachis:system:update

apply-update-force: ## Run system updater non-interactively (useful for CI/cron)
	@echo "$(CYAN)Running forced system update...$(RESET)"
	@php bin/console inachis:system:update --force --no-interaction

phpstan: ## Run PHPStan static analysis on src/
	@echo "$(CYAN)Running PHPStan analysis...$(RESET)"
	@PHPSTAN_TABLE_ERROR_FORMATTER_FORCE_SHOW_ALL_ERRORS=1 vendor/bin/phpstan analyse --memory-limit=-1 src/

phpunit: ## Run PHPUnit tests with code coverage
	@echo "$(CYAN)Running PHPUnit suite with coverage...$(RESET)"
	@XDEBUG_MODE=coverage php ./vendor/bin/phpunit --display-all-issues

qa: phpstan phpunit ## Run full QA suite (PHPStan + PHPUnit)

clean: ## Clean local build artifacts and temporary download files
	@echo "$(YELLOW)Cleaning build directory and temporary archives...$(RESET)"
	@rm -rf build/dist/*
	@rm -f /tmp/inachis-*.zip /tmp/*.download
	@echo "$(GREEN)Cleanup complete.$(RESET)"