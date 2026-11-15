<?php

// Phinx reads its database settings from the same env vars as the app (.env, .env.local, real env).
use Symfony\Component\Dotenv\Dotenv;

require_once __DIR__ . '/vendor/autoload.php';

(new Dotenv())->bootEnv(__DIR__ . '/.env');

return [
  'paths' => [
    'migrations' => '%%PHINX_CONFIG_DIR%%/db/migrations',
    'seeds' => '%%PHINX_CONFIG_DIR%%/db/seeds',
  ],
  'environments' => [
    'default_migration_table' => 'phinxlog',
    'default_environment' => 'production',
    'production' => [
      'adapter' => 'mysql',
      'host' => $_ENV['DB_HOST'],
      'name' => $_ENV['DB_NAME'],
      'user' => $_ENV['DB_USER'],
      'pass' => $_ENV['DB_PASS'],
      'port' => (int) $_ENV['DB_PORT'],
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
    ],
  ],
  'version_order' => 'creation',
];
