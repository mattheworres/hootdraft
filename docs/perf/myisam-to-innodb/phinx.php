<?php

// Phinx config for the throwaway benchmark database. Same migrations as the app, different connection.
require_once __DIR__ . '/../../vendor/autoload.php';

return [
  'paths' => [
    'migrations' => __DIR__ . '/../../db/migrations',
  ],
  'environments' => [
    'default_migration_table' => 'phinxlog',
    'default_environment' => 'perf',
    'perf' => [
      'adapter' => 'mysql',
      'host' => getenv('DB_HOST'),
      'name' => getenv('DB_NAME'),
      'user' => getenv('DB_USER'),
      'pass' => getenv('DB_PASS'),
      'port' => (int) getenv('DB_PORT'),
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
    ],
  ],
  'version_order' => 'creation',
];
