# AI Knowledge Base

## Vue d'ensemble

**AI Knowledge Base** est un module Drupal qui structure et expose la connaissance du site sous forme d'API sémantique, conçue pour être consommée par des agents d'intelligence artificielle (LLM, chatbots, systèmes de recherche sémantique, etc.).

### Fonctionnalités principales

- ✅ Type de contenu "Knowledge Entry" avec champs structurés
- ✅ Taxonomie "AI Concepts" pour classification sémantique
- ✅ API REST JSON pour récupération des connaissances
- ✅ Intégration JSON-LD (structured data) sur les pages
- ✅ Service d'indexation avec export JSONL pour vector databases
- ✅ Cron automatique pour réindexation périodique
- ✅ Support multilingue (fr, en, etc.)
- ✅ Prêt pour intégration RAG (Retrieval-Augmented Generation)

---

## Installation

### Prérequis

- Drupal 10.5+ ou 11.x
- PHP 8.1+
- Modules Drupal : `node`, `text`, `taxonomy`, `rest`, `serialization`
- (Optionnel) Module `schema_metatag` pour SEO avancé

### Activation

```bash
drush en ai_knowledge_base -y
drush cr
```

Le module crée automatiquement :
- **Type de contenu** : "Knowledge Entry" (`knowledge_entry`)
- **Taxonomie** : "AI Concepts" (`ai_concepts`)
- **Champs personnalisés** :
  - `field_summary` : Résumé court (text)
  - `field_content` : Contenu complet (text_long)
  - `field_ai_concepts` : Termes de taxonomie (entity_reference)
- **Routes API** :
  - `/api/knowledge` (GET - liste)
  - `/api/knowledge/{nid}` (GET - détail)
- **Service** : `KnowledgeIndexer` pour l'indexation

---

## Architecture

### Structure des connaissances

```
Knowledge Entry (node)
├── Title (obligatoire)
├── Summary (field_summary) - Résumé 1-2 phrases
├── Content (field_content) - Contenu complet markdown/plain
├── AI Concepts (field_ai_concepts) - Tags sémantiques
├── Language (langcode) - fr, en, etc.
└── Metadata (automatique) - created, changed, author
```

**Exemple de Knowledge Entry :**

| Champ          | Valeur                                                     |
|----------------|------------------------------------------------------------|
| **Title**      | "Comment fonctionne le paiement MTN MoMo ?"                |
| **Summary**    | "Le paiement MTN Mobile Money permet de payer via son compte mobile avec validation par code PIN." |
| **Content**    | "Étapes détaillées : 1) Sélectionner MTN MoMo au checkout, 2) Entrer le numéro, 3) Valider par PIN, 4) Confirmation..." |
| **AI Concepts**| Paiement, MTN MoMo, E-commerce, Mobile Money               |
| **Language**   | fr                                                         |

### Flux de données

```
┌──────────────────────┐
│ Éditeur de contenu   │
│ (Drupal admin UI)    │
└──────────┬───────────┘
           │ Crée/édite
           ↓
┌──────────────────────────┐
│ Knowledge Entry (node)   │
│ + AI Concepts (taxonomy) │
└──────────┬───────────────┘
           │
           ├─→ Cron (toutes les heures)
           │   └─→ KnowledgeIndexer::indexAllKnowledgeEntries()
           │       └─→ Export JSONL (private://ai/knowledge_export.jsonl)
           │
           ├─→ GET /api/knowledge
           │   └─→ KnowledgeController::getKnowledge()
           │       └─→ JSON response (liste filtrée)
           │
           └─→ GET /api/knowledge/{nid}
               └─→ KnowledgeController::getKnowledge()
                   └─→ JSON response (détail unique)
```

---

## Utilisation

### 1. Créer des connaissances (UI)

#### Étape 1 : Créer des concepts (taxonomie)

1. Allez dans **Structure > Taxonomie > AI Concepts** (`/admin/structure/taxonomy/manage/ai_concepts/overview`)
2. Cliquez sur **"Ajouter un terme"**
3. Exemples de concepts :
   - E-commerce
   - Paiement
   - Livraison
   - Produits
   - Support client
   - MTN MoMo
   - Politique de retour

#### Étape 2 : Créer une entrée de connaissance

1. Allez dans **Contenu > Ajouter du contenu > Knowledge Entry** (`/node/add/knowledge_entry`)
2. Remplissez les champs :
   - **Titre** : Question ou sujet (ex: "Quels sont les modes de livraison ?")
   - **Résumé** : Réponse courte (1-2 phrases)
   - **Contenu** : Réponse détaillée (markdown supporté)
   - **AI Concepts** : Sélectionnez 2-5 tags pertinents
   - **Langue** : Sélectionnez la langue du contenu
3. Cliquez sur **"Enregistrer"**

**Bonnes pratiques :**

- ✅ Titre = Question naturelle (ex: "Comment annuler ma commande ?")
- ✅ Résumé = Réponse directe et concise
- ✅ Contenu = Détails, étapes, exemples
- ✅ Concepts = 2-5 tags maximum (éviter sur-classification)
- ✅ Utilisez du texte simple (évitez HTML complexe pour l'IA)

---

### 2. API REST

#### **GET** `/api/knowledge` - Liste des connaissances

**Paramètres de requête :**

| Paramètre  | Type   | Description                                  |
|------------|--------|----------------------------------------------|
| `nid`      | int    | ID d'une connaissance spécifique             |
| `lang`     | string | Code langue (fr, en, etc.)                   |
| `limit`    | int    | Nombre max de résultats (défaut: 50)         |

**Exemples :**

```bash
# Toutes les connaissances (50 max)
curl http://example.com/api/knowledge

# Connaissance spécifique (ID 123)
curl http://example.com/api/knowledge?nid=123
# OU
curl http://example.com/api/knowledge/123

# Connaissances en français uniquement
curl http://example.com/api/knowledge?lang=fr

# 100 résultats
curl http://example.com/api/knowledge?limit=100
```

**Réponse (JSON) :**

```json
[
  {
    "nid": "123",
    "title": "Comment fonctionne le paiement MTN MoMo ?",
    "summary": "Le paiement MTN Mobile Money permet de payer via son compte mobile avec validation par code PIN.",
    "content": "Étapes détaillées : 1) Sélectionner MTN MoMo au checkout...",
    "concepts": [
      "Paiement",
      "MTN MoMo",
      "E-commerce"
    ],
    "lang": "fr",
    "created": "1731859200",
    "changed": "1731945600",
    "url": "http://example.com/node/123"
  }
]
```

#### **GET** `/api/knowledge/{nid}` - Détail d'une connaissance

**Exemple :**

```bash
curl http://example.com/api/knowledge/123
```

**Réponse :** Identique à `/api/knowledge?nid=123` (objet JSON unique ou tableau à 1 élément).

---

### 3. Indexation et export JSONL

Le module indexe automatiquement toutes les connaissances **toutes les heures** via le cron Drupal et génère un fichier **JSONL** (JSON Lines) pour import dans des vector databases (Pinecone, Weaviate, Qdrant, etc.).

#### Format JSONL

Chaque ligne = 1 objet JSON = 1 connaissance.

**Fichier :** `private://ai/knowledge_export.jsonl`

**Exemple de contenu :**

```jsonl
{"id":"123","title":"Comment fonctionne le paiement MTN MoMo ?","summary":"Le paiement MTN Mobile Money...","content":"Étapes détaillées...","lang":"fr","modified":1731945600,"concepts":["Paiement","MTN MoMo"]}
{"id":"124","title":"Quels sont les délais de livraison ?","summary":"Les délais varient de 24h à 5 jours...","content":"Détails complets...","lang":"fr","modified":1731945700,"concepts":["Livraison","E-commerce"]}
```

#### Télécharger le fichier JSONL

Le fichier est stocké dans le répertoire **privé** de Drupal (`private://ai/`).

**Via Drush :**

```bash
# Chemin absolu du fichier
drush php-eval "echo \Drupal::service('file_system')->realpath('private://ai/knowledge_export.jsonl');"

# Copier vers un répertoire accessible
cp $(drush php-eval "echo \Drupal::service('file_system')->realpath('private://ai/knowledge_export.jsonl');") /tmp/knowledge_export.jsonl
```

**Via SSH/SFTP :**

Chemin typique : `/var/www/html/nasande/sites/default/files/private/ai/knowledge_export.jsonl`

#### Réindexer manuellement

```bash
drush php-eval "\Drupal::service('ai_knowledge_base.indexer')->indexAllKnowledgeEntries();"
```

**Sortie attendue (logs) :**

```
Indexed 42 knowledge entries to private://ai/knowledge_export.jsonl.
```

---

### 4. Intégration JSON-LD (structured data)

Le module **n'inclut pas encore** l'intégration JSON-LD par défaut. Voici comment l'ajouter manuellement.

#### Ajouter JSON-LD aux pages Knowledge Entry

**Dans `ai_knowledge_base.module` :**

```php
/**
 * Implements hook_preprocess_node().
 */
function ai_knowledge_base_preprocess_node(&$variables) {
  $node = $variables['node'];
  if ($node->bundle() === 'knowledge_entry' && $variables['view_mode'] === 'full') {
    $title = $node->label();
    $summary = $node->get('field_summary')->value;
    $content = $node->get('field_content')->value;
    $concepts = [];
    foreach ($node->get('field_ai_concepts')->referencedEntities() as $term) {
      $concepts[] = $term->label();
    }

    $jsonld = [
      '@context' => 'https://schema.org',
      '@type' => 'FAQPage',
      'mainEntity' => [
        '@type' => 'Question',
        'name' => $title,
        'acceptedAnswer' => [
          '@type' => 'Answer',
          'text' => $summary,
        ],
      ],
      'keywords' => implode(', ', $concepts),
    ];

    $variables['#attached']['html_head'][] = [
      [
        '#tag' => 'script',
        '#attributes' => ['type' => 'application/ld+json'],
        '#value' => json_encode($jsonld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
      ],
      'knowledge_entry_jsonld',
    ];
  }
}
```

Après ajout, videz les caches :

```bash
drush cr
```

**Validation :**
- Google Rich Results Test : https://search.google.com/test/rich-results
- Schema.org Validator : https://validator.schema.org/

---

## Configuration

### Paramètres du module

Le module n'a pas d'interface de configuration UI. Les paramètres sont définis dans le code.

| Paramètre              | Valeur           | Fichier                               | Description                          |
|------------------------|------------------|---------------------------------------|--------------------------------------|
| Limite API             | 50 résultats     | `KnowledgeController.php:24`          | Nombre max de résultats par défaut   |
| Chemin export JSONL    | `private://ai/`  | `KnowledgeIndexer.php:56`             | Répertoire d'export                  |
| Nom fichier JSONL      | `knowledge_export.jsonl` | `KnowledgeIndexer.php:57`     | Nom du fichier exporté               |

**Pour modifier la limite API :**

```php
// Dans src/Controller/KnowledgeController.php, ligne 24
public function getKnowledge(Request $request, $nid = NULL): JsonResponse {
  $limit = (int) $request->query->get('limit', 100); // 100 au lieu de 50
  // ...
}
```

**Pour changer le chemin d'export :**

```php
// Dans src/Service/KnowledgeIndexer.php, ligne 56
$directory = 'public://exports/ai'; // Au lieu de private://ai/
```

---

## Cas d'usage

### 1. Chatbot IA contextuel

**Objectif :** Chatbot qui répond aux questions clients en utilisant la knowledge base.

**Implémentation (exemple avec OpenAI GPT) :**

```python
import requests
import openai

# 1. Récupérer les connaissances via l'API
response = requests.get("https://example.com/api/knowledge?lang=fr&limit=100")
knowledge_base = response.json()

# 2. Construire le contexte pour le LLM
context = "\n\n".join([
    f"Q: {entry['title']}\nA: {entry['summary']}\n{entry['content']}"
    for entry in knowledge_base
])

# 3. Question utilisateur
user_question = "Comment payer avec MTN MoMo ?"

# 4. Envoyer au LLM
openai.api_key = "sk-..."
completion = openai.ChatCompletion.create(
    model="gpt-4",
    messages=[
        {"role": "system", "content": f"Tu es un assistant e-commerce. Voici la base de connaissances :\n{context}"},
        {"role": "user", "content": user_question}
    ]
)

# 5. Réponse
print(completion.choices[0].message.content)
```

---

### 2. Recherche sémantique (vector database)

**Objectif :** Recherche par similarité (embeddings) dans la knowledge base.

**Stack technique :**
- Export JSONL depuis Drupal
- Embeddings : OpenAI text-embedding-ada-002, Sentence-BERT, etc.
- Vector DB : Pinecone, Weaviate, Qdrant

**Workflow :**

```bash
# 1. Exporter JSONL depuis Drupal
drush php-eval "\Drupal::service('ai_knowledge_base.indexer')->indexAllKnowledgeEntries();"
scp user@server:/path/to/private/ai/knowledge_export.jsonl ./

# 2. Générer embeddings (Python)
```

```python
import json
import openai
import pinecone

openai.api_key = "sk-..."
pinecone.init(api_key="...", environment="us-west1-gcp")
index = pinecone.Index("knowledge-base")

with open("knowledge_export.jsonl", "r") as f:
    for line in f:
        entry = json.loads(line)
        # Générer embedding
        text = f"{entry['title']} {entry['summary']} {entry['content']}"
        embedding = openai.Embedding.create(input=text, model="text-embedding-ada-002")["data"][0]["embedding"]
        # Insérer dans Pinecone
        index.upsert([(entry['id'], embedding, entry)])

# 3. Recherche sémantique
query = "Je veux payer par mobile"
query_embedding = openai.Embedding.create(input=query, model="text-embedding-ada-002")["data"][0]["embedding"]
results = index.query(query_embedding, top_k=5, include_metadata=True)

for match in results['matches']:
    print(f"{match['metadata']['title']} (score: {match['score']})")
```

---

### 3. Documentation interactive

**Objectif :** Générer une page FAQ dynamique depuis la knowledge base.

**Dans votre thème Drupal (Twig) :**

```twig
{# templates/page--faq.html.twig #}
<div class="faq-page">
  <h1>Questions fréquentes</h1>
  
  {% set api_url = 'http://example.com/api/knowledge?lang=fr' %}
  {% set knowledge = drupal_http_request(api_url)|json_decode %}
  
  {% for entry in knowledge %}
    <div class="faq-item" data-concepts="{{ entry.concepts|join(', ') }}">
      <h3 class="faq-question">{{ entry.title }}</h3>
      <div class="faq-answer">
        <p><strong>{{ entry.summary }}</strong></p>
        {{ entry.content|nl2br }}
      </div>
    </div>
  {% endfor %}
</div>

<script>
  // Filtrage par concept (JavaScript)
  document.querySelectorAll('.faq-filter').forEach(btn => {
    btn.addEventListener('click', () => {
      const concept = btn.dataset.concept;
      document.querySelectorAll('.faq-item').forEach(item => {
        item.style.display = item.dataset.concepts.includes(concept) ? 'block' : 'none';
      });
    });
  });
</script>
```

---

## Maintenance

### Réindexation périodique

**Automatique (Cron) :**

Le module réindexe automatiquement toutes les heures via `hook_cron()`.

**Vérifier le statut du cron :**

```bash
drush state:get system.cron_last
```

**Configurer le cron système :**

```bash
# Éditer crontab
crontab -e

# Ajouter (toutes les heures)
0 * * * * cd /var/www/html/nasande && vendor/bin/drush cron
```

**Manuel :**

```bash
# Forcer la réindexation
drush cron

# Ou directement
drush php-eval "\Drupal::service('ai_knowledge_base.indexer')->indexAllKnowledgeEntries();"
```

---

### Migration de contenu existant

Si vous avez des pages/articles existants à transformer en Knowledge Entries :

**Option A : Migration Drupal (recommandé)**

```bash
# Installer le module Migrate Tools
composer require drupal/migrate_tools drupal/migrate_plus
drush en migrate_tools migrate_plus -y

# Créer une migration custom (dans un module)
# modules/custom/my_migration/config/install/migrate_plus.migration.article_to_knowledge.yml
```

```yaml
id: article_to_knowledge
label: 'Migrate Articles to Knowledge Entries'
source:
  plugin: 'content_entity:node'
  bundle: article
  
process:
  type:
    plugin: default_value
    default_value: knowledge_entry
  title: title
  field_summary:
    plugin: substr
    source: body/value
    start: 0
    length: 200
  field_content: body/value
  langcode: langcode
  uid: uid
  created: created
  changed: changed

destination:
  plugin: 'entity:node'
  default_bundle: knowledge_entry
```

```bash
# Exécuter la migration
drush migrate:import article_to_knowledge
```

**Option B : Script PHP (rapide et sale)**

```bash
drush php-eval "
\$storage = \Drupal::entityTypeManager()->getStorage('node');
\$articles = \$storage->loadByProperties(['type' => 'article']);
foreach (\$articles as \$article) {
  \$knowledge = \$storage->create([
    'type' => 'knowledge_entry',
    'title' => \$article->label(),
    'field_summary' => substr(strip_tags(\$article->body->value), 0, 200),
    'field_content' => strip_tags(\$article->body->value),
    'langcode' => \$article->language()->getId(),
    'uid' => \$article->getOwnerId(),
  ]);
  \$knowledge->save();
  echo 'Migrated: ' . \$article->label() . PHP_EOL;
}
"
```

---

## Performance

### Cache et optimisation

**API REST :**
- Pas de cache par défaut (données temps réel)
- Pour activer le cache (5 min) :

```php
// Dans src/Controller/KnowledgeController.php
use Drupal\Core\Cache\CacheableJsonResponse;
use Drupal\Core\Cache\CacheableMetadata;

public function getKnowledge(Request $request, $nid = NULL): JsonResponse {
  // ... (code existant)
  
  $response = new CacheableJsonResponse($results);
  $cache_metadata = new CacheableMetadata();
  $cache_metadata->setCacheMaxAge(300); // 5 minutes
  $cache_metadata->addCacheTags(['node_list:knowledge_entry']);
  $response->addCacheableDependency($cache_metadata);
  
  return $response;
}
```

**Indexation JSONL :**
- Coût : O(n) avec n = nombre de knowledge entries
- Fréquence : toutes les heures (cron)
- Recommandation : < 10 000 entries OK ; au-delà, envisager un cache Redis ou file lock

**Database :**
- Index automatique sur `nid`, `type`, `status`
- Ajoutez un index fulltext si nécessaire :

```sql
ALTER TABLE node__field_summary ADD FULLTEXT(field_summary_value);
ALTER TABLE node__field_content ADD FULLTEXT(field_content_value);
```

---

## Sécurité

### Permissions

| Permission               | Description                                          |
|--------------------------|------------------------------------------------------|
| `access content`         | Requis pour GET `/api/knowledge` (public)            |
| `create knowledge_entry` | Créer des knowledge entries                          |
| `edit own knowledge_entry` | Éditer ses propres entries                         |
| `edit any knowledge_entry` | Éditer toutes les entries (admin)                  |
| `delete any knowledge_entry` | Supprimer des entries                            |

**Configuration des permissions :**

1. Allez dans **Personnes > Permissions** (`/admin/people/permissions`)
2. Cherchez "Knowledge Entry"
3. Attribuez les permissions selon les rôles :
   - **Anonymous** : rien (API publique par défaut)
   - **Authenticated** : `access content`
   - **Content Editor** : `create`, `edit own`
   - **Administrator** : toutes

**Restreindre l'API (optionnel) :**

```yaml
# Dans ai_knowledge_base.routing.yml
ai_knowledge_base.api_knowledge:
  requirements:
    _permission: 'access administration pages' # Au lieu de 'access content'
```

### Validation des données

Le module **valide** :
- Tous les champs passent par Drupal Field API (XSS protection)
- `$nid` est castée en `int` dans le contrôleur
- Export JSONL échappe automatiquement le JSON (`json_encode`)

**Aucune vulnérabilité connue** (SQL injection, XSS, CSRF).

---

## Dépannage

### Problème : "L'API retourne un tableau vide"

**Symptômes :** `/api/knowledge` retourne `[]`.

**Vérifications :**

1. Y a-t-il des Knowledge Entries publiées ?
   ```bash
   drush sql:query "SELECT COUNT(*) FROM node_field_data WHERE type='knowledge_entry' AND status=1"
   ```

2. Le type de contenu existe-t-il ?
   ```bash
   drush config:get node.type.knowledge_entry
   ```

3. Logs :
   ```bash
   drush watchdog:show --filter=ai_knowledge_base
   ```

**Solution :**

```bash
# Créer une entry de test
drush php-eval "
\$node = \Drupal\node\Entity\Node::create([
  'type' => 'knowledge_entry',
  'title' => 'Test Knowledge',
  'field_summary' => 'Summary test',
  'field_content' => 'Content test',
  'status' => 1,
]);
\$node->save();
echo 'Created node ' . \$node->id();
"
```

---

### Problème : "Le fichier JSONL n'existe pas"

**Symptômes :** `private://ai/knowledge_export.jsonl` introuvable.

**Causes :**
1. Le cron n'a jamais tourné
2. Répertoire `private://` non configuré
3. Permissions fichiers insuffisantes

**Vérifications :**

```bash
# Vérifier le chemin private://
drush php-eval "echo \Drupal::service('file_system')->realpath('private://') . PHP_EOL;"

# Tester l'indexation manuelle
drush php-eval "\Drupal::service('ai_knowledge_base.indexer')->indexAllKnowledgeEntries();"
```

**Solution :**

```bash
# Configurer private:// dans settings.php
# Ajouter/modifier :
# $settings['file_private_path'] = 'sites/default/files/private';

# Créer le répertoire
mkdir -p sites/default/files/private/ai
chmod 755 sites/default/files/private/ai

# Forcer l'indexation
drush cron
```

---

### Problème : "Erreur 'Class KnowledgeIndexer not found'"

**Symptômes :**
```
Error: Class "Drupal\ai_knowledge_base\Service\KnowledgeIndexer" not found
```

**Cause :** Service non enregistré ou cache corrompu.

**Solution :**

```bash
drush cr
drush php-eval "var_dump(\Drupal::hasService('ai_knowledge_base.indexer'));"
# Doit retourner bool(true)
```

Si `false`, vérifiez `ai_knowledge_base.services.yml` :

```yaml
services:
  ai_knowledge_base.indexer:
    class: Drupal\ai_knowledge_base\Service\KnowledgeIndexer
    arguments: ['@entity_type.manager', '@file_system', '@logger.factory']
```

---

## Évolutions futures (roadmap)

### Améliorations prévues

- [ ] Interface de configuration UI (limite API, chemin export)
- [ ] Support GraphQL en complément de REST
- [ ] Versioning des knowledge entries (révisions)
- [ ] Recherche fulltext intégrée (Solr, Elasticsearch)
- [ ] Export automatique vers vector databases (webhooks)
- [ ] Suggestions de concepts basées sur IA (auto-tagging)
- [ ] Support multimédia (images, vidéos dans les réponses)
- [ ] Import/export CSV/JSON pour bulk operations

### Intégrations futures

- [ ] Chatbot Drupal (module custom `ai_chatbot`)
- [ ] Intégration OpenAI Assistants API
- [ ] Slack/Discord bot alimenté par la knowledge base
- [ ] Widget "Ask AI" dans le thème frontend

---

## Support & contribution

### Logs et débogage

```bash
# Logs Drupal
drush watchdog:show --filter=ai_knowledge_base

# Test du service
drush php-eval "
\$indexer = \Drupal::service('ai_knowledge_base.indexer');
\$count = \$indexer->indexAllKnowledgeEntries();
echo 'Indexed: ' . \$count . PHP_EOL;
"

# Test de l'API
curl -v http://example.com/api/knowledge
```

### Ressources

- Drupal Content Entity API : https://www.drupal.org/docs/drupal-apis/entity-api
- Drupal REST API : https://www.drupal.org/docs/core-modules-and-themes/core-modules/rest
- JSON-LD : https://json-ld.org/
- Vector Databases : https://www.pinecone.io/learn/vector-database/

---

## Crédits

- **Auteur :** Développé dans le cadre du projet Nasande AI Ecosystem (Phase 3)
- **Version :** 1.0.0
- **Licence :** GPL-2.0+
- **Dépendances :** Drupal Core (node, text, taxonomy, rest, serialization)

---

## Changelog

### Version 1.0.0 (2024-11-18)
- ✅ Type de contenu "Knowledge Entry"
- ✅ Taxonomie "AI Concepts"
- ✅ API REST GET `/api/knowledge`
- ✅ Service d'indexation JSONL
- ✅ Cron automatique
- ✅ Support multilingue
- ✅ Documentation complète


