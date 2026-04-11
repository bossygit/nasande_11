# ==============================================================================
# Makefile — Drupal Workflow for nasande.cg
# ==============================================================================
#
# Usage:
#   make install       Install dependencies + rebuild cache
#   make export        Export Drupal config to YAML
#   make import        Import YAML config into Drupal
#   make sync-db       Download production database to local
#   make update        Run DB updates + config import + cache rebuild
#   make deploy        Export config, commit, push to main (triggers CI/CD)
#   make status        Show Drupal status
#   make lint          PHP syntax check on custom modules
#
# ==============================================================================

DRUSH := php vendor/bin/drush
COMPOSER := php composer.phar

.DEFAULT_GOAL := help

.PHONY: help install export import sync-db update deploy status lint cr

help: ## Show this help
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | \
		awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2}'

install: ## Install Composer dependencies + rebuild cache
	$(COMPOSER) install
	$(DRUSH) cache:rebuild

export: ## Export Drupal configuration to config/sync
	$(DRUSH) config:export -y
	@echo ""
	@echo "Config exported. Don't forget to commit:"
	@echo "  git add ../config/sync/ && git commit -m 'Update config export'"

import: ## Import configuration from config/sync into Drupal
	$(DRUSH) config:import -y
	$(DRUSH) cache:rebuild

sync-db: ## Sync production database to local
	$(DRUSH) sql-sync @prod @self -y
	$(DRUSH) cache:rebuild
	@echo ""
	@echo "Production DB synced to local."

update: ## Run DB updates + config import + cache rebuild
	$(DRUSH) updatedb -y
	$(DRUSH) config:import -y
	$(DRUSH) cache:rebuild

deploy: export ## Export config, commit all changes, push to main
	@if [ -z "$$(git status --porcelain)" ]; then \
		echo "Nothing to commit."; \
	else \
		git add -A; \
		read -p "Commit message: " msg; \
		git commit -m "$$msg"; \
		git push origin main; \
		echo ""; \
		echo "Pushed to main. GitHub Actions will deploy automatically."; \
	fi

status: ## Show Drupal site status
	$(DRUSH) status

lint: ## PHP syntax check on custom modules
	@find modules/custom -name "*.php" -print0 | xargs -0 -n1 php -l 2>&1 | grep -v "No syntax errors" || echo "All files OK"

cr: ## Rebuild Drupal cache
	$(DRUSH) cache:rebuild
