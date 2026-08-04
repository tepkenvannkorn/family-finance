<?php
/** @var array $rows */
/** @var string $dateFrom */
/** @var string $dateTo */
use App\Core\View;
$symbols = ['USD' => '$', 'KHR' => '៛'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #1e293b; }
    h1 { font-size: 16px; margin-bottom: 4px; }
    p.range { color: #64748b; margin-top: 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    th { background: #f8fafc; }
    td.amount { text-align: right; }
</style>
</head>
<body>
    <h1>VK Finance — Transaction Report</h1>
    <p class="range"><?= View::e($dateFrom) ?> to <?= View::e($dateTo) ?></p>
    <table>
        <thead>
            <tr><th>Date</th><th>Type</th><th>Category</th><th>Description</th><th>Amount</th><th>By</th></tr>
        </thead>
        <tbody>
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td><?= View::e($row['transaction_date']) ?></td>
                    <td><?= View::e(ucfirst($row['type'])) ?></td>
                    <td><?= View::e($row['category_name']) ?></td>
                    <td><?= View::e($row['description']) ?></td>
                    <td class="amount"><?= $symbols[$row['currency']] ?? '' ?><?= number_format((float) $row['amount'], 2) ?> <?= View::e($row['currency']) ?></td>
                    <td><?= View::e($row['created_by_name'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
