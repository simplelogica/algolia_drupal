<?php

namespace Drupal\algolia;

class AlgoliaAdapter {

  public static function updateIndexSettings() {
    $config = self::getConfiguration();
    foreach ($config as $indexName => $indexSettings) {
      $client = self::createClient($indexSettings['account']);
      $index = $client->initIndex($indexName);
      $index->setSettings($indexSettings["config"]);
    }
    watchdog("algolia", "Indexes settings updated.", [], WATCHDOG_INFO);
  }

  public static function deleteObject($node) {
    $nodes = is_array($node) ? $node : [$node];
    self::deleteMultipleObjects($nodes);
    watchdog("algolia", "Deleted @count nodes from indexes.", ["@count" => count($nodes)], WATCHDOG_INFO);
  }

  public static function updateObject($node) {
    $nodes = is_array($node) ? $node : [$node];
    self::updateMultipleObjects($nodes);
    watchdog("algolia", "Updated @count nodes from indexes.", ["@count" => count($nodes)], WATCHDOG_INFO);
  }

  public static function addObject($node) {
    $nodes = is_array($node) ? $node : [$node];
    self::addMultipleObjects($nodes);
    watchdog("algolia", "Added @count nodes to indexes.", ["@count" => count($nodes)], WATCHDOG_INFO);
  }

  private static function deleteMultipleObjects($nodes) {
    $config = self::getConfiguration();
    foreach ($nodes as $node) {
      $affectedIndexes = self::getIndexesForNode($config, $node);
      foreach ($affectedIndexes as $indexName => $indexSettings) {
        $client = self::createClient($indexSettings['account']);
        $index = $client->initIndex($indexName);
        $index->deleteObject($node->nid);
      }
    }
  }

  private static function addMultipleObjects($nodes) {
    $config = self::getConfiguration();
    foreach ($nodes as $node) {
      $affectedIndexes = self::getIndexesForNode($config, $node);
      foreach ($affectedIndexes as $indexName => $indexSettings) {
        $client = self::createClient($indexSettings['account']);
        $index = $client->initIndex($indexName);
        $serializer = $indexSettings["content"][$node->type];
        $index->addObject(call_user_func($serializer, $node), $node->nid);
      }
    }
  }

  private static function updateMultipleObjects($nodes) {
    $config = self::getConfiguration();
    foreach ($nodes as $node) {
      $affectedIndexes = self::getIndexesForNode($config, $node);
      foreach ($affectedIndexes as $indexName => $indexSettings) {
        $client = self::createClient($indexSettings['account']);
        $index = $client->initIndex($indexName);
        $serializer = $indexSettings["content"][$node->type];
        // The update function requires the object id to be specified along with
        // the serialized arguments.
        $serializedObject = array_merge(call_user_func($serializer, $node), ["objectID" => $node->nid]);
        $index->saveObject($serializedObject);
      }
    }
  }

  // TODO: documentar en el README
  private static function getConfiguration() {
    $customizationModules = module_implements('configure_algolia');
    return array_reduce($customizationModules, function($carry, $module) {
      return array_merge($carry, module_invoke($module, 'configure_algolia'));
    }, []);
  }

  // Returns a list of indexes that may contain a given node.
  private static function getIndexesForNode($config, $node) {
    return array_filter($config, function ($index) use ($node) {
      return isset($index['content']) && array_key_exists($node->type, $index['content']);
    });
  }

  // TODO: documentar en el README
  private static function createClient($accountName) {
    $accounts = variable_get("algolia_accounts");
    if (!$accounts) {
      throw new \Exception("You must configure an Algolia account in your settings.php");
    }
    if (!array_key_exists($accountName, $accounts)) {
      throw new \Exception("The Algolia account '{$accountName}' is not configured in settings.php");
    }
    $applicationId = $accounts[$accountName]["application_id"];
    if (!$applicationId) {
      throw new \Exception("You must configure an application ID for Algolia account '{$accountName}' in your settings.php");
    }
    $apiKey = $accounts[$accountName]["admin_api_key"];
    if (!$apiKey) {
      throw new \Exception("You must configure an API key for Algolia account '{$accountName}' in your settings.php");
    }
    return new \AlgoliaSearch\Client($applicationId, $apiKey);
  }
}
