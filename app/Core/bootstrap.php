<?php

declare(strict_types=1);

/**
 * Shared bootstrap: loads Composer autoloader + .env variables.
 * Required at the top of every CLI script and (later) public/index.php.
 */

$root = dirname(__DIR__, 2);

require_once $root . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($root, ['.env']);
$dotenv->safeLoad(); // safeLoad: won't error if .env is missing (e.g. CI), falls back to config defaults

date_default_timezone_set($_ENV['APP_TIMEZONE'] ?? 'UTC');

return $root;
