# Algolia adapter for Drupal

This module provides a base adapter that can be used by other modules to integrate
with Algolia indexation services.

This module hooks automatically into the node lifecycle to trigger a sinchronization
when a node is created, updated or deleted.  It also sets a hook to trigger
a full indexation on every cron run.

## Installation

1. Download the Algolia PHP client into the _libraries_ folder of your Drupal
application and rename its folder to _algoliasearch_.
2. Download this module into your _modules_ directory. And enable it with the
command `drush en algolia`.
3. Create your own customization module to configure indexes, content-types and
serializations (see next section).

## Setup

In your `settings.php` you must configure the applications that will be used
for the Algolia integration. You can have as many applications as you want.

```php
$conf["algolia_accounts"] = [
  "application_1" => [
    "application_id" => "SUPER-SECRET-APP-ID",
    "admin_api_key" => "SUPER-SECRET-ADMIN-API-KEY",
    "api_key" => "SUPER-SECRET-SEARCH-API-KEY",
  ],
  "application_2" => [
    "application_id" => "SUPER-SECRET-APP-ID",
    "admin_api_key" => "SUPER-SECRET-ADMIN-API-KEY",
    "api_key" => "SUPER-SECRET-SEARCH-API-KEY",
  ],
  ...
];
```

## Extension

This module is meant to provide a generic functionality that can be extended by
other modules to indicate which indexes exist, which content-types belong to each
index and how they are serialized before being sent to Algolia.

You must create a module and include a dependency in your `yourmodule.info`
file:

```php
dependencies[] = algolia
```

Then you must implement `hook_configure_algolia()` in your `yourmodule.module`
file. The hook must return an array like the following one:

```php
'my_index' => [
  'account' => 'my_account',
  'config' => [
    'attributesToIndex' => ['title', 'body', 'description'],
    'slaves' => ['my_index_slave']
  ],
  'content' => [
    'content_type_a' => 'my_serialization_callback',
    'content_type_b' => 'my_other_serialization_callback',
  ],
],
'my_index_slave' => [
  'account' => 'alumni',
  'config' => [
    'attributesToIndex' => ['title', 'body']
  ],
],
...
```

The array has the names of the existing indexes as keys. Each index must state the
account it belongs to, its configuration and its content types. The values set
in `config` are passed as-is to Algolia.

The `content` key is used during indexation to know which content types belong
to a given index. For each content type we define a serialization callback that
will receive a node of the given content type, and must return an array with the
attributes defined in the index. For example:

```php
function my_serialization_callback($node) {
  // $node is always of type `content_type_a`
  return [
    'title' => $node->title,
    'body' => $node->body,
    'description' => $node->body->summary
  ];
}
```
