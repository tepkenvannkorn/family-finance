<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Role
{
    public int $id;
    public string $name;
    public ?string $description;

    /** @return self[] */
    public static function all(): array
    {
        $stmt = Database::connection()->query('SELECT * FROM roles ORDER BY id ASC');
        return array_map(self::hydrate(...), $stmt->fetchAll());
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM roles WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    private static function hydrate(array $row): self
    {
        $role = new self();
        $role->id = (int) $row['id'];
        $role->name = $row['name'];
        $role->description = $row['description'];
        return $role;
    }
}
