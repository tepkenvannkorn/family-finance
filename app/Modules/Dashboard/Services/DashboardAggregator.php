<?php

declare(strict_types=1);

namespace App\Modules\Dashboard\Services;

use App\Core\Database;
use App\Models\ExchangeRate;
use App\Modules\Transactions\Services\CurrencyConverter;

final class DashboardAggregator
{
    public function __construct(private CurrencyConverter $converter = new CurrencyConverter())
    {
    }

    /**
     * @param int|null $userId restrict to one user's own transactions (members); null = everyone (admin)
     * @param string $displayCurrency 'original' | 'USD' | 'KHR'
     */
    public function summary(?int $userId, string $displayCurrency): array
    {
        $rate = $this->converter->latestRate();
        $rows = $this->totalsByTypeAndCurrency($userId);

        $totals = ['income' => '0', 'expense' => '0'];
        foreach ($rows as $row) {
            $amount = $displayCurrency === 'original'
                ? $row['total'] // mixed-currency mode: caller shows per-currency breakdown separately
                : $this->converter->convert($row['total'], $row['currency'], $displayCurrency, $rate);
            $totals[$row['type']] = bcadd($totals[$row['type']], $amount, 2);
        }

        return [
            'income' => $totals['income'],
            'expense' => $totals['expense'],
            'balance' => bcsub($totals['income'], $totals['expense'], 2),
            'by_currency' => $rows,
            'exchange_rate' => $rate,
        ];
    }

    public function incomeVsExpense(?int $userId, int $months = 6): array
    {
        $stmt = $this->prepareMonthly(
            "SELECT DATE_FORMAT(transaction_date, '%Y-%m') AS period, type, currency, SUM(amount) AS total
             FROM transactions WHERE deleted_at IS NULL AND transaction_date >= :since",
            $userId,
            $months
        );
        return $stmt->fetchAll();
    }

    public function weeklyTrend(?int $userId, int $weeks = 8): array
    {
        $since = (new \DateTimeImmutable("-{$weeks} weeks"))->format('Y-m-d');
        $sql = "SELECT YEARWEEK(transaction_date, 3) AS period, type, currency, SUM(amount) AS total
                FROM transactions WHERE deleted_at IS NULL AND transaction_date >= :since";
        $params = ['since' => $since];
        if ($userId !== null) {
            $sql .= ' AND created_by = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' GROUP BY period, type, currency ORDER BY period ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function expenseByCategory(?int $userId, string $dateFrom, string $dateTo): array
    {
        $sql = "SELECT c.name AS category, t.currency, SUM(t.amount) AS total
                FROM transactions t JOIN categories c ON c.id = t.category_id
                WHERE t.deleted_at IS NULL AND t.type = 'expense'
                  AND t.transaction_date BETWEEN :from AND :to";
        $params = ['from' => $dateFrom, 'to' => $dateTo];
        if ($userId !== null) {
            $sql .= ' AND t.created_by = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' GROUP BY c.name, t.currency ORDER BY total DESC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function recentTransactions(?int $userId, int $limit = 10): array
    {
        $sql = "SELECT t.*, c.name AS category_name, u.name AS created_by_name
                FROM transactions t
                JOIN categories c ON c.id = t.category_id
                JOIN users u ON u.id = t.created_by
                WHERE t.deleted_at IS NULL";
        $params = [];
        if ($userId !== null) {
            $sql .= ' AND t.created_by = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' ORDER BY t.transaction_date DESC, t.transaction_time DESC LIMIT :limit';

        $stmt = Database::connection()->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    private function totalsByTypeAndCurrency(?int $userId): array
    {
        $sql = "SELECT type, currency, COALESCE(SUM(amount), 0) AS total FROM transactions WHERE deleted_at IS NULL";
        $params = [];
        if ($userId !== null) {
            $sql .= ' AND created_by = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' GROUP BY type, currency';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    private function prepareMonthly(string $baseSql, ?int $userId, int $months): \PDOStatement
    {
        $since = (new \DateTimeImmutable("first day of -{$months} months"))->format('Y-m-d');
        $sql = $baseSql;
        $params = ['since' => $since];
        if ($userId !== null) {
            $sql .= ' AND created_by = :user_id';
            $params['user_id'] = $userId;
        }
        $sql .= ' GROUP BY period, type, currency ORDER BY period ASC';

        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }
}
