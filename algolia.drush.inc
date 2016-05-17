<?php

/**
 * Implements `hook_drush_command()`.
 */
function algolia_drush_command() {
  $commands["algolia-update-index-settings"] = [
    "description" => "Updates the settings for all defined indexes",
  ];
  $commands["algolia-index-content"] = [
    "description" => "Trigges a re-index of all applicable content",
    "options" => [
      "type" => "The content type to reindex"
    ],
    "examples" => [
      "algolia-index-content" => "Trigges a reindex of all content",
      "algolia-index-content --type=project" => "Trigges a reindex of all content of type 'project'",
    ],
  ];
  return $commands;
}

function drush_algolia_update_index_settings() {
  Drupal\algolia\AlgoliaAdapter::updateIndexSettings();
}

function drush_algolia_index_content() {
  $content_type = drush_get_option("type", null);
  Drupal\algolia\Workers\IndexWorker::index($content_type);
}
