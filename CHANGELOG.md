# Changelog — nasande.cg

Tous les changements notables de ce projet sont documentés dans ce fichier.

---

## [2026-04-11] — Mise en place du workflow local + CI/CD

### 🗄️ Base de données
- Import du dump SQL production (`nasacdka_nasande.sql`) dans la base locale `nasacdka_nasande_local`
- Nettoyage du dump SQL (suppression de l'erreur HTML phpMyAdmin "MySQL server gone away")

### ⚙️ Configuration
- **`sites/default/settings.php`** :
  - Suppression des credentials de production hardcodés
  - Ajout du chargement des credentials via variables d'environnement (`DRUPAL_DB_NAME`, etc.)
  - Activation du chargement de `settings.local.php` (doit être en dernier dans le fichier)
  - Mise à jour de `config_sync_directory` vers `../config/sync`
  - Ajout de `localhost` dans `trusted_host_patterns`
  - Désactivation de `$settings['https']` (géré par `settings.local.php` en local)
  - Désactivation de `update_free_access` (réactivé en local via `settings.local.php`)
- **`sites/default/settings.local.php`** (NEW) :
  - Credentials DB locale MAMP (`nasacdka_nasande_local` / `root` / `root`)
  - Connexion via Unix socket MAMP
  - Caching désactivé pour le développement
  - Erreurs verboses
  - HTTPS désactivé
- **`.env.example`** (NEW) :
  - Template de variables d'environnement pour la production

### 🔧 Git & Versionning
- **`.gitignore`** (NEW) — Exclut :
  - `core/`, `vendor/`, `modules/contrib/`, `themes/contrib/` (Composer)
  - `sites/*/settings.local.php`, `*.sql`, `error_log`
  - `sites/*/files/`, `sites/*/private/`
- **`config/sync/`** (NEW) — Dossier pour les exports de config Drupal (YAML)

### 🚀 CI/CD
- **`.github/workflows/deploy.yml`** (NEW) :
  - Pipeline GitHub Actions déclenché sur push vers `main`
  - Déploiement via SSH : `git pull` → `composer install` → `drush updatedb` → `drush config:import` → `drush cache:rebuild`
  - Secrets requis : `SSH_HOST`, `SSH_USER`, `SSH_KEY`, `SSH_PORT`, `DEPLOY_PATH`

### 📦 Module custom
- **`modules/custom/nasande_updates/`** (NEW) :
  - Module utilitaire pour versionner les changements de schéma DB
  - Template `hook_update_N()` avec exemples documentés
  - Convention de numérotation : `10001+` (Drupal 10), `11001+` (Drupal 11)

---

## Workflow — Commandes de référence

### Dev local
```bash
# Exporter la config après modifications dans l'admin Drupal
vendor/bin/drush config:export -y

# Créer un update hook si changement DB → éditer nasande_updates.install

# Commit + push
git add -A && git commit -m "description" && git push origin main
```

### Déploiement (automatique via GitHub Actions)
```bash
# Le push sur main déclenche automatiquement :
git pull origin main
composer install --no-dev
vendor/bin/drush updatedb -y
vendor/bin/drush config:import -y
vendor/bin/drush cache:rebuild
```

### Sync DB prod → local
```bash
# Via drush (nécessite alias @prod configuré)
vendor/bin/drush sql-sync @prod @local

# Ou via phpMyAdmin : exporter prod, importer en local
```
