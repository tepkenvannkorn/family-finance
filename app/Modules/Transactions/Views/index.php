<?php
/** @var array $rows */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
/** @var \App\Models\Category[] $categories */
/** @var string $roleName */
/** @var int $userId */
/** @var string|null $success */
/** @var string|null $error */
use App\Core\View;

$initialRows = View::render('Transactions::_rows', [
    'rows' => $rows, 'total' => $total, 'page' => $page, 'perPage' => $perPage,
    'filters' => $filters, 'roleName' => $roleName, 'userId' => $userId,
], layout: null);
?>
<div x-data="transactionSearch()" x-init="init()">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-semibold text-slate-800">Transactions</h1>
        <a href="/transactions/create" class="rounded-lg bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
            + Add transaction
        </a>
    </div>

    <?php if ($success): ?>
        <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3"><?= View::e($success) ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3"><?= View::e($error) ?></div>
    <?php endif; ?>

    <div class="bg-white rounded-xl border border-slate-200 p-4 mb-4 grid grid-cols-2 md:grid-cols-4 gap-3">
        <input type="text" x-model="filters.keyword" @input.debounce.400ms="page = 1; search()"
               placeholder="Search description/notes…"
               class="col-span-2 rounded-lg border border-slate-300 px-3 py-2 text-sm">

        <select x-model="filters.type" @change="page = 1; search()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All types</option>
            <option value="income">Income</option>
            <option value="expense">Expense</option>
        </select>

        <select x-model="filters.currency" @change="page = 1; search()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All currencies</option>
            <option value="USD">USD</option>
            <option value="KHR">KHR</option>
        </select>

        <select x-model="filters.category_id" @change="page = 1; search()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <option value="">All categories</option>
            <?php foreach ($categories as $category): ?>
                <option value="<?= $category->id ?>"><?= View::e($category->name) ?> (<?= $category->type ?>)</option>
            <?php endforeach; ?>
        </select>

        <input type="date" x-model="filters.date_from" @change="page = 1; search()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="date" x-model="filters.date_to" @change="page = 1; search()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="number" step="0.01" x-model="filters.amount_min" @input.debounce.400ms="page = 1; search()" placeholder="Min amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <input type="number" step="0.01" x-model="filters.amount_max" @input.debounce.400ms="page = 1; search()" placeholder="Max amount" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div id="results"><?= $initialRows ?></div>
</div>

<script>
function transactionSearch() {
    return {
        filters: {
            keyword: <?= json_encode($filters['keyword'] ?? '') ?>,
            type: <?= json_encode($filters['type'] ?? '') ?>,
            currency: <?= json_encode($filters['currency'] ?? '') ?>,
            category_id: <?= json_encode((string) ($filters['category_id'] ?? '')) ?>,
            date_from: <?= json_encode($filters['date_from'] ?? '') ?>,
            date_to: <?= json_encode($filters['date_to'] ?? '') ?>,
            amount_min: <?= json_encode($filters['amount_min'] ?? '') ?>,
            amount_max: <?= json_encode($filters['amount_max'] ?? '') ?>,
        },
        page: <?= (int) $page ?>,
        init() {},
        search() {
            const params = new URLSearchParams({ ...this.filters, page: this.page });
            fetch('/transactions/search?' + params.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.text())
                .then(html => {
                    const el = document.getElementById('results');
                    el.innerHTML = html;
                    if (window.Alpine) { window.Alpine.initTree(el); }
                });
        }
    };
}
</script>
