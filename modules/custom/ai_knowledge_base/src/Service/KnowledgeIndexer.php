<?php

namespace Drupal\ai_knowledge_base\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\File\FileSystemInterface;
use Psr\Log\LoggerInterface;

class KnowledgeIndexer {
  public function __construct(
    protected EntityTypeManagerInterface $entityTypeManager,
    protected FileSystemInterface $fileSystem,
    protected LoggerInterface $logger,
  ) {}

  public function exportJsonl(string $destination = 'private://ai/knowledge_export.jsonl'): string {
    $storage = $this->entityTypeManager->getStorage('node');
    $nids = $storage->getQuery()->condition('type', 'knowledge_entry')->condition('status', 1)->execute();
    $nodes = $storage->loadMultiple($nids);

    $dir = dirname($destination);
    $this->fileSystem->prepareDirectory($dir, FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS);

    $realpath = \Drupal::service('file_system')->realpath($destination);
    if (!$realpath) {
      $scheme = parse_url($destination, PHP_URL_SCHEME) ?: 'private';
      $wrapper = \Drupal::service('stream_wrapper_manager')->getViaScheme($scheme);
      $realpath = rtrim($wrapper->realpath(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . ltrim(parse_url($destination, PHP_URL_PATH), '/');
    }

    $fh = fopen($realpath, 'w');
    foreach ($nodes as $node) {
      $record = [
        'id' => (int) $node->id(),
        'title' => $node->label(),
        'summary' => $node->get('field_summary')->value ?? '',
        'content' => $node->get('field_content')->value ?? '',
        'lang' => $node->language()->getId(),
        'modified' => (int) $node->getChangedTime(),
      ];
      fwrite($fh, json_encode($record, JSON_UNESCAPED_UNICODE) . "\n");
    }
    fclose($fh);
    $this->logger->info('Knowledge export written to @path', ['@path' => $destination]);
    return $destination;
  }
}


