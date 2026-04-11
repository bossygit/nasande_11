# AI User Tracker

## Vue d'ensemble

**AI User Tracker** est un module Drupal qui capture, stocke et expose les comportements des utilisateurs pour alimenter des systèmes d'intelligence artificielle et d'analyse comportementale. Il s'intègre nativement avec Drupal Commerce et Google Analytics 4.

### Fonctionnalités principales

- ✅ Capture automatique des événements client et serveur
- ✅ Intégration native Drupal Commerce (produits, panier, commandes)
- ✅ API REST pour exposition des données comportementales
- ✅ Pont GA4 (Google Analytics 4) pour tous les événements
- ✅ Vue préconfigurée "Produits les plus vus"
- ✅ Rapport administrateur des événements
- ✅ Purge automatique des données anciennes (RGPD)
- ✅ Support des sessions anonymes (localStorage)

---

## Installation

### Prérequis

- Drupal 10.5+ ou 11.x
- PHP 8.1+
- Modules Drupal : `rest`, `serialization`, `views`, `user`
- (Optionnel) Drupal Commerce 2.x pour les événements e-commerce

### Activation

```bash
drush en ai_user_tracker -y
drush cr
```

Le module crée automatiquement :
- Table `ai_user_behavior` (schéma complet)
- Vue "Produits les plus vus" (`/produits-plus-vus`)
- Routes API REST (`/api/user/behavior`, `/api/user/behavior/track`)
- Rapport admin (`/admin/reports/ai-user-behavior`)

---

## Architecture

### Base de données

**Table : `ai_user_behavior`**

| Colonne       | Type    | Description                                      |
|---------------|---------|--------------------------------------------------|
| `id`          | serial  | Clé primaire auto-incrémentée                    |
| `uid`         | int     | ID utilisateur Drupal (0 = anonyme)              |
| `session_id`  | varchar | UUID de session (localStorage côté client)       |
| `page_path`   | varchar | Chemin de la page (URL)                          |
| `event_type`  | varchar | Type d'événement (page_view, cart_add, etc.)     |
| `metadata`    | text    | Données JSON (contexte, produit, prix, etc.)     |
| `created`     | int     | Timestamp UNIX de création                       |
| `product_id`  | int     | ID produit Commerce (si applicable)              |

**Index :**
- `uid_created` : (uid, created)
- `event_type` : (event_type)
- `product_id` : (product_id)

### Flux de données

```
┌─────────────────┐
│  Navigateur     │
│  (JavaScript)   │
└────────┬────────┘
         │ sendBeacon/AJAX
         ↓
┌─────────────────────────────┐
│ /api/user/behavior/track    │ ← POST
│ BehaviorController::track   │
└────────┬────────────────────┘
         │ INSERT
         ↓
┌─────────────────────────────┐       ┌──────────────────┐
│   ai_user_behavior (DB)     │←──────│ Commerce Events  │
│                             │       │ (EventSubscriber)│
└────────┬────────────────────┘       └──────────────────┘
         │ SELECT
         ↓
┌─────────────────────────────┐
│ /api/user/behavior (GET)    │
│ Vue "Produits les plus vus" │
│ Rapport admin               │
└─────────────────────────────┘
```

---

## Événements capturés

### Côté client (JavaScript - `ai_user_tracker.js`)

| Événement        | Déclencheur                              | Metadata                                      |
|------------------|------------------------------------------|-----------------------------------------------|
| `page_view`      | Chargement de page                       | `title`                                       |
| `click`          | Clic sur lien/bouton                     | `tag`, `text`, `href`, `id`, `classes`        |
| `scroll_depth`   | Scroll 25/50/75/100%                     | `percent`                                     |
| `add_to_cart`    | Soumission formulaire panier             | `product_id`, `price`, `currency`, `title`    |
| `remove_from_cart` | Clic bouton retrait panier             | `product_id`, `title`                         |
| `begin_checkout` | Entrée tunnel checkout                   | `path`                                        |

### Côté serveur (PHP - `CommerceEventSubscriber`)

| Événement         | Route/Condition                          | Metadata                                      |
|-------------------|------------------------------------------|-----------------------------------------------|
| `product_view`    | `entity.commerce_product.canonical`      | `route`, `path`, `product_id`                 |
| `cart_add`        | Route panier + Commerce                  | `product_id`, `price`, `currency`, `item_name`|
| `cart_remove`     | Route retrait + Commerce                 | `product_id`, `price`, `currency`, `item_name`|
| `checkout_start`  | `commerce_checkout.form*`                | `route`, `path`, `order_id`                   |
| `checkout_complete` | `commerce_checkout.complete`           | `route`, `path`, `order_id`                   |

### Intégration GA4

Tous les événements sont **automatiquement envoyés à Google Analytics 4** via `gtag()` si le script GA4 est présent sur la page.

**Événements spéciaux GA4 :**

- `purchase` (checkout complete) : inclut `transaction_id`, `value`, `currency`, `items[]` avec détails complets des produits

---

## Utilisation

### 1. API REST

#### **GET** `/api/user/behavior` - Récupérer les événements

**Paramètres de requête :**

| Paramètre     | Type   | Description                                |
|---------------|--------|--------------------------------------------|
| `uid`         | int    | Filtrer par ID utilisateur                 |
| `event_type`  | string | Filtrer par type d'événement               |
| `session_id`  | string | Filtrer par session                        |
| `limit`       | int    | Nombre max de résultats (défaut: 10)       |

**Exemples :**

```bash
# Tous les événements (10 derniers)
curl http://example.com/api/user/behavior

# Événements d'un utilisateur
curl http://example.com/api/user/behavior?uid=5

# Vues produit uniquement
curl http://example.com/api/user/behavior?event_type=product_view

# 50 derniers événements
curl http://example.com/api/user/behavior?limit=50
```

**Réponse (JSON) :**

```json
[
  {
    "id": "123",
    "uid": "1",
    "session_id": "a1b2c3d4-e5f6-7890-abcd-ef1234567890",
    "page_path": "/product/5",
    "event_type": "product_view",
    "metadata": {
      "route": "entity.commerce_product.canonical",
      "path": "/product/5",
      "product_id": 5
    },
    "created": "1731859200",
    "product_id": "5"
  }
]
```

#### **POST** `/api/user/behavior/track` - Enregistrer un événement

**Headers :**
```
Content-Type: application/json
```

**Body (JSON) :**

```json
{
  "event_type": "custom_event",
  "page_path": "/custom-page",
  "metadata": {
    "key": "value",
    "action": "test"
  },
  "session_id": "uuid-optional"
}
```

**Réponse :**
```json
{"status":"ok"}
```

**Exemple curl :**

```bash
curl -X POST http://example.com/api/user/behavior/track \
  -H "Content-Type: application/json" \
  -d '{
    "event_type": "custom_action",
    "page_path": "/test",
    "metadata": {"source": "api", "value": 42}
  }'
```

---

### 2. Rapport administrateur

**URL :** `/admin/reports/ai-user-behavior`

**Permission requise :** `administer site configuration`

**Fonctionnalités :**
- Tableau des 100 derniers événements
- Colonnes : ID, User, Event Type, Page Path, Metadata (JSON), Product ID, Timestamp
- Tri par date décroissante

---

### 3. Vue "Produits les plus vus"

**Page :** `/produits-plus-vus`

**Bloc :** "Produits les plus vus" (Structure > Mise en page des blocs)

**Fonctionnalités :**
- Agrégation des `product_view` par produit
- Compteur de vues par produit
- Tri par nombre de vues (décroissant)
- Affichage : titre produit (lié), nombre de vues
- Limite : 12 produits par défaut

**Personnalisation :**

1. Allez dans **Structure > Vues > Produits les plus vus**
2. Ajoutez des champs (image, prix, SKU) via la relation "Product"
3. Changez le style (grille, liste, slider)
4. Filtrez par période (7/30 jours) via un filtre sur "Created"
5. Créez des variantes de bloc (Top 5, Top 10, etc.)

**Exemple : Top 5 des 7 derniers jours**

1. Clonez le display "Bloc"
2. Ajoutez un filtre "Created" : `>= -7 days`
3. Changez le pager : "Afficher X éléments" → `5`
4. Sauvegardez sous un nouveau nom de bloc

---

### 4. Événements personnalisés

#### Côté client (JavaScript)

**Option A : Attribut HTML**

```html
<button data-track-click>Mon bouton personnalisé</button>
```

**Option B : Code JavaScript**

```javascript
// Dans votre module/thème JS
(function ($, Drupal) {
  Drupal.behaviors.monTracking = {
    attach: function (context, settings) {
      $('.mon-element', context).once('monTracking').on('click', function () {
        var payload = {
          event_type: 'custom_click',
          page_path: window.location.pathname,
          metadata: { element: 'special-button', value: 123 },
          session_id: localStorage.getItem('ai_tracker_sid')
        };
        
        navigator.sendBeacon(
          '/api/user/behavior/track',
          new Blob([JSON.stringify(payload)], { type: 'application/json' })
        );
      });
    }
  };
})(jQuery, Drupal);
```

#### Côté serveur (PHP)

```php
// Dans un contrôleur, hook ou service
\Drupal::database()->insert('ai_user_behavior')
  ->fields([
    'uid' => \Drupal::currentUser()->id(),
    'session_id' => NULL,
    'page_path' => \Drupal::request()->getPathInfo(),
    'event_type' => 'custom_server_event',
    'metadata' => json_encode(['key' => 'value']),
    'created' => \Drupal::time()->getRequestTime(),
    'product_id' => NULL, // ou ID si pertinent
  ])
  ->execute();
```

---

## Configuration

### Paramètres du module

Le module n'a pas d'interface de configuration UI. Les paramètres sont définis en dur dans le code :

| Paramètre              | Valeur           | Fichier                           | Description                          |
|------------------------|------------------|-----------------------------------|--------------------------------------|
| Durée de rétention     | 180 jours        | `ai_user_tracker.module:116`      | Événements purgés au-delà            |
| Limite API GET         | 10 résultats     | `BehaviorController.php:33`       | Nombre max de résultats par défaut   |
| Clé session localStorage | `ai_tracker_sid` | `ai_user_tracker.js:13`          | Clé pour UUID session côté client    |

**Pour modifier la durée de rétention :**

```php
// Dans ai_user_tracker.module, ligne 116
function ai_user_tracker_cron() {
  $threshold = REQUEST_TIME - (90 * 24 * 60 * 60); // 90 jours au lieu de 180
  // ...
}
```

**Pour modifier la limite API :**

```php
// Dans src/Controller/BehaviorController.php, ligne 33
public function getBehavior(Request $request): JsonResponse {
  $limit = (int) $request->query->get('limit', 50); // 50 au lieu de 10
  // ...
}
```

---

## Maintenance

### Purge automatique (Cron)

Le module **supprime automatiquement** les événements > 180 jours via `hook_cron()`.

**Déclenchement :**
- Automatique : si le cron Drupal est configuré (recommandé)
- Manuel : `drush cron`

**Vérifier le dernier cron :**
```bash
drush state:get system.cron_last
```

**Configurer le cron système (crontab) :**
```bash
# Éditer crontab
crontab -e

# Ajouter (toutes les heures)
0 * * * * cd /var/www/html/nasande && vendor/bin/drush cron
```

### Purge manuelle

```bash
# Supprimer tous les événements > 180 jours
drush sql:query "DELETE FROM ai_user_behavior WHERE created < UNIX_TIMESTAMP(DATE_SUB(NOW(), INTERVAL 180 DAY))"

# Supprimer tous les événements (reset complet)
drush sql:query "TRUNCATE TABLE ai_user_behavior"
```

### Export des données

```bash
# Export CSV (tous les événements)
drush sql:query "SELECT * FROM ai_user_behavior ORDER BY created DESC" --extra=--csv > export.csv

# Export JSON (produits vus uniquement)
drush sql:query "SELECT * FROM ai_user_behavior WHERE event_type='product_view'" --extra=--json > product_views.json

# Top 10 produits
drush sql:query "SELECT product_id, COUNT(*) as views FROM ai_user_behavior WHERE event_type='product_view' AND product_id IS NOT NULL GROUP BY product_id ORDER BY views DESC LIMIT 10"
```

---

## Performance

### Optimisation base de données

Les index sont créés automatiquement à l'installation :
- `uid_created` : requêtes par utilisateur + tri chronologique
- `event_type` : filtrage par type d'événement
- `product_id` : agrégation produits (vue "Produits les plus vus")

**Pour vérifier les index :**
```bash
drush sql:query "SHOW INDEX FROM ai_user_behavior"
```

### Cache et mise en cache

- **API GET** : pas de cache par défaut (données temps réel)
- **Vue "Produits les plus vus"** : cache de type `tag` (invalidé au changement de contenu)
- **Rapport admin** : pas de cache

**Pour activer le cache API (custom) :**

Ajoutez dans `BehaviorController.php` :

```php
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

public function getBehavior(Request $request): JsonResponse {
  // ... (code existant)
  
  $response = new CacheableJsonResponse($results);
  $cache_metadata = new CacheableMetadata();
  $cache_metadata->setCacheMaxAge(300); // 5 minutes
  $response->addCacheableDependency($cache_metadata);
  
  return $response;
}
```

---

## Sécurité & RGPD

### Données personnelles

Le module collecte :
- ✅ **UID** (utilisateur Drupal) : pseudonyme, pas d'identification directe
- ✅ **Session ID** (UUID client) : non lié à l'identité
- ✅ **Chemin de page** : URL visitée (peut contenir des paramètres sensibles)
- ⚠️ **Metadata** : peut contenir des données personnelles selon usage custom

**Conformité RGPD :**
1. **Purge automatique** : 180 jours (droit à l'oubli)
2. **Anonymisation** : UID = 0 pour anonymes
3. **Minimisation** : pas de cookies tiers, pas d'IP stockée
4. **Consentement** : ajoutez un bandeau cookie si nécessaire (ex: via module Cookie Consent)

### Permissions

| Permission               | Description                                          |
|--------------------------|------------------------------------------------------|
| `access content`         | Requis pour GET `/api/user/behavior` (public)        |
| `access content`         | Requis pour POST `/api/user/behavior/track` (public) |
| `administer site configuration` | Requis pour rapport admin                    |

**⚠️ Attention** : L'API est **publique** par défaut. Pour restreindre :

```yaml
# Dans ai_user_tracker.routing.yml
ai_user_tracker.behavior_get:
  requirements:
    _permission: 'administer users' # Au lieu de 'access content'
```

### Validation des entrées

Le module **valide** :
- `event_type` : max 64 caractères, échappé
- `page_path` : max 512 caractères
- `metadata` : JSON valide, échappé à l'insertion
- `session_id` : max 128 caractères

Aucune donnée utilisateur n'est exécutée sans échappement (protection XSS/SQL injection).

---

## Dépannage

### Problème : "Le JavaScript ne se charge pas"

**Symptômes :** Aucun événement client (page_view, click, scroll) dans la base.

**Vérifications :**
1. Console navigateur (F12) : erreurs JS ?
2. Onglet Réseau : le fichier `ai_user_tracker.js` se charge-t-il ?
3. Drush : `drush cr` pour vider les caches
4. Vérifiez que la bibliothèque est attachée :
   ```bash
   drush config:get ai_user_tracker.libraries
   ```

**Solution :**
```bash
drush cr
# Vérifier que le module est activé
drush pm:list --status=enabled | grep ai_user_tracker
```

---

### Problème : "Les événements Commerce ne sont pas capturés"

**Symptômes :** Pas de `cart_add`, `checkout_complete`, etc.

**Vérifications :**
1. Drupal Commerce est-il activé ?
   ```bash
   drush pm:list --status=enabled | grep commerce
   ```
2. Les routes existent-elles ?
   ```bash
   drush route:debug | grep commerce_cart
   ```
3. Logs : `/admin/reports/dblog` (filtre: ai_user_tracker)

**Solution :**
```bash
drush en commerce_cart commerce_checkout -y
drush cr
```

---

### Problème : "La vue 'Produits les plus vus' est vide"

**Symptômes :** La page `/produits-plus-vus` affiche "Aucun résultat".

**Causes possibles :**
1. Aucun événement `product_view` en base
2. Colonne `product_id` NULL pour tous les événements
3. Problème de relation Views

**Vérifications :**
```bash
# Compter les vues produit
drush sql:query "SELECT COUNT(*) FROM ai_user_behavior WHERE event_type='product_view' AND product_id IS NOT NULL"

# Vérifier la relation Views
drush config:get views.view.most_viewed_products display.default.relationships
```

**Solution :**
1. Visitez des pages produit (URL : `/product/1`, `/product/2`, etc.)
2. Vérifiez que le subscriber fonctionne :
   ```bash
   drush sql:query "SELECT * FROM ai_user_behavior WHERE event_type='product_view' ORDER BY created DESC LIMIT 5"
   ```
3. Si `product_id` est toujours NULL, vérifiez `CommerceEventSubscriber.php` (méthode `extractProductId()`)

---

### Problème : "Erreur 'Too few arguments' lors de l'update 8101"

**Symptômes :**
```
Too few arguments to function Schema::addIndex(), 3 passed
```

**Cause :** Syntaxe incorrecte de `addIndex()` dans le hook d'update.

**Solution :** Déjà corrigée dans `ai_user_tracker_update_8101()`. Si l'erreur persiste :

```bash
# Ajouter manuellement la colonne product_id
drush sql:query "ALTER TABLE ai_user_behavior ADD COLUMN product_id INT UNSIGNED NULL"
drush sql:query "CREATE INDEX product_id ON ai_user_behavior (product_id)"

# Marquer l'update comme exécuté
drush sql:query "UPDATE key_value SET value='i:8101;' WHERE collection='system.schema' AND name='ai_user_tracker'"
```

---

## Évolutions futures (roadmap)

### Phase 4 : Module AI Recommender (à venir)

- Recommandations personnalisées basées sur `ai_user_behavior`
- Algorithmes : collaborative filtering, content-based
- Bloc "Produits recommandés pour vous"
- API ML/LLM externe (OpenAI, etc.)

### Phase 5 : Adaptive UX (à venir)

- CTAs dynamiques selon comportement
- Bannières adaptatives
- Chatbot intégrant `/api/knowledge` + historique user

### Améliorations possibles

- [ ] Interface de configuration UI (durée rétention, limite API)
- [ ] Support GraphQL en complément de REST
- [ ] Export automatique vers entrepôts de données (BigQuery, Snowflake)
- [ ] Dashboard analytics temps réel (Chart.js, D3.js)
- [ ] Intégration Matomo/Plausible en alternative à GA4
- [ ] Webhooks pour événements critiques (abandon panier, etc.)

---

## Support & contribution

### Logs et débogage

**Logs Drupal :**
```bash
# Via UI
/admin/reports/dblog

# Via Drush
drush watchdog:show --filter=ai_user_tracker

# Temps réel
drush watchdog:tail --filter=ai_user_tracker
```

**Activer le mode debug JS :**

Dans `ai_user_tracker.js`, ajoutez :
```javascript
console.log('AI Tracker: event sent', eventType, meta);
```

### Ressources

- Documentation Drupal Views : https://www.drupal.org/docs/user_guide/views-chapter
- Drupal Commerce Events : https://docs.drupalcommerce.org/commerce2/developer-guide/core/events
- GA4 Events Reference : https://developers.google.com/analytics/devguides/collection/ga4/events

---

## Crédits

- **Auteur :** Développé dans le cadre du projet Nasande AI Ecosystem
- **Version :** 1.0.0
- **Licence :** GPL-2.0+
- **Dépendances :** Drupal Core (rest, serialization, views, user), Drupal Commerce (optionnel)

---

## Changelog

### Version 1.0.0 (2024-11-18)
- ✅ Capture automatique événements client/serveur
- ✅ Intégration Drupal Commerce
- ✅ API REST GET/POST
- ✅ Vue "Produits les plus vus"
- ✅ Rapport admin
- ✅ Purge automatique (cron)
- ✅ Pont GA4
- ✅ Support product_id dans événements Commerce


