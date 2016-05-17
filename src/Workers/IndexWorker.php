<?php

namespace Drupal\algolia\Workers;

use EntityFieldQuery;
use Drupal\algolia\AlgoliaAdapter;

class IndexWorker {

  public static function index($contentType = null) {
    watchdog("algolia", "Content reindexation started.", [], WATCHDOG_INFO);
    self::updatePublished($contentType);
    watchdog("algolia", "Published content updated in indexes.", [], WATCHDOG_INFO);
    self::removeUnpublished($contentType);
    watchdog("algolia", "Unpublished content removed from indexes.", [], WATCHDOG_INFO);
    watchdog("algolia", "Content reindexation finished.", [], WATCHDOG_INFO);
  }

  private static function updatePublished($contentType) {
    $query = new EntityFieldQuery();
    $query->entityCondition("entity_type", "node")
      ->propertyCondition("status", NODE_PUBLISHED);
    if ($contentType) {
      $query->entityCondition("bundle", $contentType);
    }
    $result = $query->execute();
    if (empty($result)) {
      return;
    }
    $nodes = node_load_multiple(array_keys($result["node"]));
    AlgoliaAdapter::updateObject($nodes);
  }

  private static function removeUnpublished($contentType) {
    $query = new EntityFieldQuery();
    $query->entityCondition("entity_type", "node")
    ->propertyCondition("status", NODE_NOT_PUBLISHED);
    if ($contentType) {
      $query->entityCondition("bundle", $contentType);
    }
    $result = $query->execute();
    if (empty($result)) {
      return;
    }
    $nodes = node_load_multiple(array_keys($result["node"]));
    AlgoliaAdapter::deleteObject($nodes);
  }
}
