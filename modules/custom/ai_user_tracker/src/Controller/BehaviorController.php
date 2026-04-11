<?php

namespace Drupal\ai_user_tracker\Controller;

use Drupal; 
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class BehaviorController extends ControllerBase {

  /**
   * GET /api/user/behavior
   */
  public function list(Request $request): JsonResponse {
    $limit = (int) $request->query->get('limit', 50);
    $uid = $request->query->get('uid');
    $connection = Drupal::database();

    $query = $connection->select('ai_user_behavior', 'b')
      ->fields('b', ['id', 'uid', 'session_id', 'page_path', 'event_type', 'created']);
    if ($uid !== NULL && $uid !== '') {
      $query->condition('uid', (int) $uid);
    }
    $query->orderBy('created', 'DESC')
      ->range(0, max(1, min(500, $limit)));

    $rows = $query->execute()->fetchAllAssoc('id');

    return new JsonResponse([
      'count' => count($rows),
      'items' => array_values($rows),
    ]);
  }

  /**
   * POST /api/user/behavior/track
   */
  public function track(Request $request): JsonResponse {
    $payload = json_decode($request->getContent() ?? '[]', TRUE) ?: [];
    $eventType = $payload['event_type'] ?? NULL;
    $pagePath = $payload['page_path'] ?? '/';
    $metadata = $payload['metadata'] ?? [];
    $sessionId = $payload['session_id'] ?? NULL;

    if (!$eventType) {
      return new JsonResponse(['error' => 'Missing event_type'], 400);
    }

    $uid = (int) $this->currentUser()->id();
    $connection = Drupal::database();

    $connection->insert('ai_user_behavior')
      ->fields([
        'uid' => $uid,
        'session_id' => $sessionId,
        'page_path' => substr((string) $pagePath, 0, 512),
        'event_type' => substr((string) $eventType, 0, 64),
        'metadata' => !empty($metadata) ? json_encode($metadata) : NULL,
        'created' => time(),
      ])->execute();

    return new JsonResponse(['status' => 'ok']);
  }

  /**
   * Admin reporting page: /admin/reports/ai-user-behavior
   */
  public function admin() {
    $header = [
      ['data' => $this->t('ID')],
      ['data' => $this->t('User')],
      ['data' => $this->t('Event')],
      ['data' => $this->t('Path')],
      ['data' => $this->t('Meta')],
      ['data' => $this->t('Created')],
    ];

    $rows = [];
    $result = Drupal::database()->select('ai_user_behavior', 'b')
      ->fields('b', ['id', 'uid', 'session_id', 'page_path', 'event_type', 'metadata', 'created'])
      ->orderBy('created', 'DESC')
      ->range(0, 100)
      ->execute();

    foreach ($result as $row) {
      $rows[] = [
        'data' => [
          (int) $row->id,
          (int) $row->uid,
          $row->event_type,
          $row->page_path,
          $row->metadata ? substr($row->metadata, 0, 120) : '',
          \Drupal::service('date.formatter')->format((int) $row->created, 'short'),
        ],
      ];
    }

    $build['table'] = [
      '#type' => 'table',
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No events found.'),
    ];

    $build['api_link'] = [
      '#markup' => Link::fromTextAndUrl($this->t('View JSON API'), Url::fromUri('internal:/api/user/behavior'))->toString(),
    ];

    return $build;
  }
}


