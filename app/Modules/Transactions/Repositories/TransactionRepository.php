<?php

declare(strict_types=1);

namespace App\Modules\Transactions\Repositories;

use App\Core\Database;
use PDO;

/**
 * All read queries for the transaction list/search/reports go through here
 * so the SQL for filters (date range, type, currency, category, user,
 * keyword, amount range) is written once and reused by both the
 * Transactions list (Phase 5) and Reports (Phase 7).
 */
final class TransactionRepository
{
    /**
     * @param array{
     *   date_from?: string, date_to?: string, type?: string, currency?: string,
     *   category_id?: int, user_id?: int, keyword?: string,
     *   amount_min?: string, amount_max?: string
     * } $filters
     * @return array{rows: array, total: int}
     */
    public function filtered(array $filters, string $sortBy, string $sortDir, int $page, int $perPage): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $allowedSorts = ['transaction_date', 'amount', 'type', 'currency', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'transaction_date';
        $sortDir = strtoupper($sortDir) === 'ASC' ? 'ASC' : 'DESC';

        $db = Database::connection();

        $countStmt = $db->prepare("SELECT COUNT(*) FROM transactions t {$where}");
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $offset = max(0, ($page - 1) * $perPage);
        $sql = "SELECT t.*, c.name AS category_name, c.type AS category_type, u.name AS created_by_name
                FROM transactions t
                JOIN categories c ON c.id = t.category_id
                JOIN users u ON u.id = t.created_by
                {$where}
                ORDER BY t.{$sortBy} {$sortDir}, t.transaction_time {$sortDir}
                LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($sql);
        foreach ($params as $key => $value) {
            $stmt->bindValue(':' . $key, $value);
        }
        $stmt->bindValue('limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return ['rows' => $stmt->fetchAll(), 'total' => $total];
    }

    /**
     * @return array{income: string, expense: string} totals per currency, keyed "income_USD" etc.
     */
    public function totalsByCurrency(array $filters): array
    {
        [$where, $params] = $this->buildWhere($filters);

        $stmt = Database::connection()->prepare(
            "SELECT type, currency, COALESCE(SUM(amount), 0) AS total
             FROM transactions t {$where}
             GROUP BY type, currency"
        );
        $stmt->execute($params);

        $totals = [];
        foreach ($stmt->fetchAll() as $row) {
            $totals["{$row['type']}_{$row['currency']}"] = $row['total'];
        }
        return $totals;
    }

    private function buildWhere(array $filters): array
    {
        $conditions = ['t.deleted_at IS NULL'];
        $params = [];

        if (!empty($filters['date_from'])) {
            $conditions[] = 't.transaction_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }
        if (!empty($filters['date_to'])) {
            $conditions[] = 't.transaction_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }
        if (!empty($filters['type'])) {
            $conditions[] = 't.type = :type';
            $params['type'] = $filters['type'];
        }
        if (!empty($filters['currency'])) {
            $conditions[] = 't.currency = :currency';
            $params['currency'] = $filters['currency'];
        }
        if (!empty($filters['category_id'])) {
            $conditions[] = 't.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }
        if (!empty($filters['user_id'])) {
            $conditions[] = 't.created_by = :user_id';
            $params['user_id'] = $filters['user_id'];
        }
        // if (!empty($filters['keyword'])) {
        //     $conditions[] = '(t.description LIKE :keyword OR t.notes LIKE :keyword)';
        //     $params['keyword'] = '%' . $filters['keyword'] . '%';
        // }
        if (!empty($filters['keyword'])) {
            $conditions[] = '(
                t.description LIKE :keyword_description
                OR t.notes LIKE :keyword_notes
            )';

            $keyword = '%' . $filters['keyword'] . '%';

            $params['keyword_description'] = $keyword;
            $params['keyword_notes'] = $keyword;
        }
        if (!empty($filters['amount_min'])) {
            $conditions[] = 't.amount >= :amount_min';
            $params['amount_min'] = $filters['amount_min'];
        }
        if (!empty($filters['amount_max'])) {
            $conditions[] = 't.amount <= :amount_max';
            $params['amount_max'] = $filters['amount_max'];
        }

        return ['WHERE ' . implode(' AND ', $conditions), $params];
    }
}
