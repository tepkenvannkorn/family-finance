<?php
/** @var array $rows */
/** @var array $totals */
/** @var string $period */
/** @var string $dateFrom */
/** @var string $dateTo */
/** @var \App\Models\Category[] $categories */
/** @var string $roleName */
/** @var array $query */
use App\Core\View;

$symbols = ['USD' => '$', 'KHR' => '៛'];
$qs = fn (array $overrides = []) => http_build_query(array_merge($query, ['period' => $period, 'date_from' => $dateFrom, 'date_to' => $dateTo], $overrides));
?>
<style>
    @media print {
        nav, .no-print { display: none !important; }
        main { max-width: none !important; padding: 0 !important; }
    }
</style>

<div class="flex items-center justify-between mb-6 no-print">
    <h1 class="text-xl font-semibold text-slate-800">Reports</h1>
    <div class="flex gap-2">
        <a href="/reports/export/csv?<?= $qs() ?>" class="rounded-lg border border-slate-300 text-sm px-3 py-2 hover:bg-slate-50">Export CSV</a>
        <a href="/reports/export/excel?<?= $qs() ?>" class="rounded-lg border border-slate-300 text-sm px-3 py-2 hover:bg-slate-50">Export Excel</a>
        <a href="/reports/export/pdf?<?= $qs() ?>" class="rounded-lg border border-slate-300 text-sm px-3 py-2 hover:bg-slate-50">Export PDF</a>
        <button onclick="window.print()" class="rounded-lg bg-slate-800 text-white text-sm px-3 py-2 hover:bg-slate-700">Print</button>
    </div>
</div>

<form method="GET" action="/reports" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 grid grid-cols-2 md:grid-cols-5 gap-3 no-print">
    <select name="period" onchange="this.form.submit()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <?php foreach (['daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom range'] as $value => $label): ?>
            <option value="<?= $value ?>" <?= $period === $value ? 'selected' : '' ?>><?= $label ?></option>
        <?php endforeach; ?>
    </select>

    <?php if ($period === 'custom'): ?>
        <input type="date" name="date_from" value="<?= View::e($dateFrom) ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="date" name="date_to" value="<?= View::e($dateTo) ?>" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
    <?php endif; ?>

    <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">All types</option>
        <option value="income" <?= ($query['type'] ?? '') === 'income' ? 'selected' : '' ?>>Income</option>
        <option value="expense" <?= ($query['type'] ?? '') === 'expense' ? 'selected' : '' ?>>Expense</option>
    </select>

    <select name="category_id" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="">All categories</option>
        <?php foreach ($categories as $category): ?>
            <option value="<?= $category->id ?>" <?= (int) ($query['category_id'] ?? 0) === $category->id ? 'selected' : '' ?>>
                <?= View::e($category->name) ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit" class="rounded-lg bg-blue-600 text-white text-sm px-3 py-2 hover:bg-blue-700">Apply</button>
</form>

<p class="text-sm text-slate-500 mb-4"><?= View::e($dateFrom) ?> – <?= View::e($dateTo) ?></p>

<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    <?php foreach ($totals as $key => $total): ?>
        <?php [$type, $currency] = explode('_', $key); ?>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500"><?= ucfirst($type) ?> (<?= $currency ?>)</p>
            <p class="text-lg font-semibold <?= $type === 'income' ? 'text-green-700' : 'text-red-700' ?>">
                <?= $symbols[$currency] ?? '' ?><?= number_format((float) $total, 2) ?>
            </p>
        </div>
    <?php endforeach; ?>
</div>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">Date</th>
                <th class="px-4 py-3 font-medium">Type</th>
                <th class="px-4 py-3 font-medium">Category</th>
                <th class="px-4 py-3 font-medium">Description</th>
                <th class="px-4 py-3 font-medium text-right">Amount</th>
                <?php if ($roleName === 'admin'): ?><th class="px-4 py-3 font-medium">By</th><?php endif; ?>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
                <tr>
                    <td class="px-4 py-3"><?= View::e($row['transaction_date']) ?></td>
                    <td class="px-4 py-3"><?= View::e(ucfirst($row['type'])) ?></td>
                    <td class="px-4 py-3"><?= View::e($row['category_name']) ?></td>
                    <td class="px-4 py-3"><?= View::e($row['description']) ?></td>
                    <td class="px-4 py-3 text-right"><?= $symbols[$row['currency']] ?? '' ?><?= number_format((float) $row['amount'], 2) ?> <?= $row['currency'] ?></td>
                    <?php if ($roleName === 'admin'): ?><td class="px-4 py-3"><?= View::e($row['created_by_name']) ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-400">No transactions in this period.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
