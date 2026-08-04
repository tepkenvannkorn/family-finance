<?php

declare(strict_types=1);

require dirname(__DIR__) . '/vendor/autoload.php';

$root = dirname(__DIR__);
if (is_file($root . '/.env.testing')) {
    Dotenv\Dotenv::createImmutable($root, '.env.testing')->safeLoad();
} elseif (is_file($root . '/.env')) {
    Dotenv\Dotenv::createImmutable($root, '.env')->safeLoad();
}

date_default_timezone_set('UTC');
