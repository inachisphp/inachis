.PHONY: help release release-patch release-minor release-major build-release check-update apply-update apply-update-force phpstan phpunit qa clean

# Color output helpers
CYAN := \033[36m
GREEN := \033[32m
YELLOW := \033[33m
RESET := \033[0m

TYPE ?= patch

help: ## Display available commands
	@echo "$(CYAN)Inachis Framework Management & Build Commands$(RESET)"
	@echo ""
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-18s$(RESET) %s\n", $$1, $$2}'

release: ## Bump version, update CHANGELOG.md, commit, and tag (TYPE=patch|minor|major)
	@echo "$(CYAN)Checking git status...$(RESET)"
	@if [ -n "$$(git status --porcelain)" ]; then \
		echo "$(YELLOW)Error: Working directory is dirty. Commit or stash changes first.$(RESET)"; \
		exit 1; \
	fi
	@echo "$(CYAN)Fetching latest changes...$(RESET)"
	git pull origin main
	$(eval NEW_VERSION := $(shell php bin/console app:release:bump $(TYPE) | grep 'VERSION=' | cut -d'=' -f2))
	@if [ -z "$(NEW_VERSION)" ]; then \
		echo "$(YELLOW)Error: Failed to generate new version number.$(RESET)"; \
		exit 1; \
	fi
	@echo "$(CYAN)Staging release files...$(RESET)"
	git add composer.json CHANGELOG.md
	@echo "$(CYAN)Committing release...$(RESET)"
	git commit -m "chore(release): prepare v$(NEW_VERSION)"
	@echo "$(CYAN)Tagging v$(NEW_VERSION)...$(RESET)"
	git tag -a "v$(NEW_VERSION)" -m "Release v$(NEW_VERSION)"
	@echo "$(CYAN)Pushing commits and tags to origin...$(RESET)"
	git push origin main
	git push origin "v$(NEW_VERSION)"
	@echo ""
	@echo "$(GREEN)✅ Release v$(NEW_VERSION) tagged and pushed successfully!$(RESET)"
	@echo "$(CYAN)👉 Next step: Run 'make build-release' to package release binaries.$(RESET)"

release-patch: ## Shortcut to trigger a patch version bump release (x.x.X)
	@$(MAKE) release TYPE=patch

release-minor: ## Shortcut to trigger a minor version bump release (x.X.0)
	@$(MAKE) release TYPE=minor

release-major: ## Shortcut to trigger a major version bump release (X.0.0)
	@$(MAKE) release TYPE=major

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
