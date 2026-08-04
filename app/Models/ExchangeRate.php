<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class ExchangeRate
{
    public int $id;
    public string $rate;
    public string $source;
    public string $fetchedAt;
    public ?int $createdBy;

    public static function latest(): ?self
    {
        $stmt = Database::connection()->query(
            "SELECT * FROM exchange_rates WHERE base_currency = 'USD' AND quote_currency = 'KHR'
             ORDER BY fetched_at DESC LIMIT 1"
        );
        $row = $stmt->fetch();
        return $row ? self::hydrate($row) : null;
    }

    /** @return self[] */
    public static function history(int $limit = 30): array
    {
        $stmt = Database::connection()->prepare(
            "SELECT * FROM exchange_rates WHERE base_currency = 'USD' AND quote_currency = 'KHR'
             ORDER BY fetched_at DESC LIMIT :limit"
        );
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return array_map(self::hydrate(...), $stmt->fetchAll());
    }

    public static function record(string $rate, string $source, ?int $createdBy): self
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO exchange_rates (base_currency, quote_currency, rate, source, created_by)
             VALUES ('USD', 'KHR', :rate, :source, :created_by)"
        );
        $stmt->execute(['rate' => $rate, 'source' => $source, 'created_by' => $createdBy]);

        return self::latest();
    }

    private static function hydrate(array $row): self
    {
        $r = new self();
        $r->id = (int) $row['id'];
        $r->rate = $row['rate'];
        $r->source = $row['source'];
        $r->fetchedAt = $row['fetched_at'];
        $r->createdBy = $row['created_by'] !== null ? (int) $row['created_by'] : null;
        return $r;
    }
}
