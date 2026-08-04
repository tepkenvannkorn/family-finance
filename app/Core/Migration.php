<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Base class for a single migration file.
 * Each migration is one reversible unit of schema change.
 */
abstract class Migration
{
    abstract public function up(\PDO $db): void;

    abstract public function down(\PDO $db): void;
}
