<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class Category
{
    public int $id;
    public string $type;
    public string $name;
    public ?string $icon;
    public ?string $color;
    public bool $isActive;
    public int $sortOrder;

    /** @return self[] */
    public static function active(?string $type = null): array
    {
        $sql = 'SELECT * FROM categories WHERE is_active = 1';
        $params = [];
        if ($type !== null) {
            $sql .= ' AND type = :type';
            $params['type'] = $type;
        }
        $sql .= ' ORDER BY sort_order ASC, name ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return array_map(self::hydrate(...), $stmt->fetchAll());
    }

    public static function findById(int $id): ?self
    {
        $stmt = Database::connection()->prepare('SELECT * FROM categories WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    private static function hydrate(array $row): self
    {
        $category = new self();
        $category->id = (int) $row['id'];
        $category->type = $row['type'];
        $category->name = $row['name'];
        $category->icon = $row['icon'];
        $category->color = $row['color'];
        $category->isActive = (bool) $row['is_active'];
        $category->sortOrder = (int) $row['sort_order'];
        return $category;
    }
}
