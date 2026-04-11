<?php

namespace Drupal\ai_knowledge_base\Controller;

use Drupal;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class KnowledgeController extends ControllerBase {

  public function list(Request $request): JsonResponse {
    $q = trim((string) $request->query->get('q', ''));
    $concept = $request->query->get('concept');
    $lang = $request->query->get('lang');
    $limit = (int) $request->query->get('limit', 20);
    $page = max(0, (int) $request->query->get('page', 0));

    $storage = Drupal::entityTypeManager()->getStorage('node');
    $query = $storage->getQuery()->accessCheck(TRUE)
      ->condition('type', 'knowledge_entry')
      ->condition('status', 1)
      ->range($page * $limit, max(1, min(100, $limit)))
      ->sort('changed', 'DESC');

    if ($q !== '') {
      $group = $query->orConditionGroup()
        ->condition('title', '%' . Drupal::database()->escapeLike($q) . '%', 'LIKE')
        ->condition('field_summary.value', '%' . Drupal::database()->escapeLike($q) . '%', 'LIKE')
        ->condition('field_content.value', '%' . Drupal::database()->escapeLike($q) . '%', 'LIKE');
      $query->condition($group);
    }
    if ($concept) {
      // Accept tid or term name.
      if (is_numeric($concept)) {
        $query->condition('field_ai_concepts.target_id', (int) $concept);
      }
      else {
        $tids = Drupal::entityQuery('taxonomy_term')->condition('vid', 'ai_concepts')->condition('name', $concept)->execute();
        if ($tids) {
          $query->condition('field_ai_concepts.target_id', array_values($tids), 'IN');
        }
      }
    }
    if ($lang) {
      $query->condition('langcode', $lang);
    }

    $nids = $query->execute();
    $nodes = $storage->loadMultiple($nids);
    $items = [];
    foreach ($nodes as $node) {
      $items[] = $this->serializeNode($node, FALSE);
    }

    $response = new JsonResponse([
      'count' => count($items),
      'items' => $items,
    ]);
    $response->setPublic();
    $response->headers->set('Cache-Control', 'max-age=300, public');
    return $response;
  }

  public function detail($nid): JsonResponse {
    /** @var \Drupal\node\Entity\Node|null $node */
    $node = Node::load((int) $nid);
    if (!$node || $node->bundle() !== 'knowledge_entry' || !$node->isPublished()) {
      return new JsonResponse(['error' => 'Not found'], 404);
    }
    return new JsonResponse($this->serializeNode($node, TRUE));
  }

  protected function serializeNode(Node $node, bool $withContent = TRUE): array {
    $concepts = [];
    foreach ($node->get('field_ai_concepts')->referencedEntities() as $term) {
      /** @var Term $term */
      $concepts[] = [
        'tid' => (int) $term->id(),
        'name' => $term->getName(),
      ];
    }

    $jsonld = [
      '@context' => 'https://schema.org',
      '@type' => 'CreativeWork',
      'headline' => $node->label(),
      'description' => $node->get('field_summary')->value ?? '',
      'about' => array_map(static fn($c) => $c['name'], $concepts),
      'inLanguage' => $node->language()->getId(),
      'dateModified' => gmdate('c', (int) $node->getChangedTime()),
      'url' => Url::fromRoute('entity.node.canonical', ['node' => $node->id()], ['absolute' => TRUE])->toString(),
    ];

    return [
      'id' => (int) $node->id(),
      'title' => $node->label(),
      'summary' => $node->get('field_summary')->value ?? '',
      'content' => $withContent ? ($node->get('field_content')->value ?? '') : NULL,
      'concepts' => $concepts,
      'language' => $node->language()->getId(),
      'modified' => (int) $node->getChangedTime(),
      'jsonld' => $jsonld,
    ];
  }

  public function admin() {
    $header = [
      ['data' => $this->t('ID')],
      ['data' => $this->t('Title')],
      ['data' => $this->t('Concepts')],
      ['data' => $this->t('Modified')],
    ];
    $rows = [];
    $storage = Drupal::entityTypeManager()->getStorage('node');
    $nids = $storage->getQuery()->condition('type', 'knowledge_entry')->sort('changed', 'DESC')->range(0, 50)->execute();
    $nodes = $storage->loadMultiple($nids);
    foreach ($nodes as $node) {
      $concept_names = array_map(static fn($c) => $c['name'], $this->serializeNode($node, FALSE)['concepts']);
      $rows[] = [
        'data' => [
          (int) $node->id(),
          Link::fromTextAndUrl($node->label(), Url::fromRoute('entity.node.canonical', ['node' => $node->id()]))->toString(),
          implode(', ', $concept_names),
          \Drupal::service('date.formatter')->format((int) $node->getChangedTime(), 'short'),
        ],
      ];
    }

    return [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No knowledge entries.'),
    ];
  }
}


