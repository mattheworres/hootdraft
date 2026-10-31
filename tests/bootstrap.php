<?php

use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Filesystem;

require dirname(__DIR__) . '/vendor/autoload.php';

(new Dotenv())->bootEnv(dirname(__DIR__) . '/.env');

// Some tests boot a non-debug kernel, whose cached container is never checked for freshness: start every run clean
(new Filesystem())->remove(dirname(__DIR__) . '/var/cache/test');
