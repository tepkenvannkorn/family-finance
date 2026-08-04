<?php
/** @var array $rows */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var array $filters */
/** @var string $roleName */
/** @var int $userId */
use App\Core\View;
use App\Modules\Transactions\Policies\TransactionPolicy;
use App\Models\Transaction;

$totalPages = max(1, (int) ceil($total / $perPage));
$symbols = ['USD' => '$', 'KHR' => '៛'];
?>
<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">Date</th>
                <th class="px-4 py-3 font-medium">Type</th>
                <th class="px-4 py-3 font-medium">Category</th>
                <th class="px-4 py-3 font-medium">Description</th>
                <th class="px-4 py-3 font-medium text-right">Amount</th>
                <th class="px-4 py-3 font-medium">Currency</th>
                <?php if ($roleName === 'admin'): ?><th class="px-4 py-3 font-medium">By</th><?php endif; ?>
                <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $row): ?>
                <?php
                    // Reconstruct a lightweight Transaction to reuse the policy check for ownership rules.
                    $stub = new Transaction();
                    $stub->id = (int) $row['id'];
                    $stub->createdBy = (int) $row['created_by'];
                    $canEdit = TransactionPolicy::canEdit($stub, $userId, $roleName);
                    $canDelete = TransactionPolicy::canDelete($stub, $userId, $roleName);
                ?>
                <tr>
                    <td class="px-4 py-3 text-slate-600"><?= View::e($row['transaction_date']) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs <?= $row['type'] === 'income' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?>">
                            <?= View::e(ucfirst($row['type'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3 text-slate-600"><?= View::e($row['category_name']) ?></td>
                    <td class="px-4 py-3 text-slate-800"><?= View::e($row['description']) ?></td>
                    <td class="px-4 py-3 text-right font-medium <?= $row['type'] === 'income' ? 'text-green-700' : 'text-red-700' ?>">
                        <?= $symbols[$row['currency']] ?? '' ?><?= number_format((float) $row['amount'], 2) ?>
                    </td>
                    <td class="px-4 py-3 text-slate-500"><?= View::e($row['currency']) ?></td>
                    <?php if ($roleName === 'admin'): ?>
                        <td class="px-4 py-3 text-slate-500"><?= View::e($row['created_by_name']) ?></td>
                    <?php endif; ?>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        <a href="/transactions/<?= $row['id'] ?>/edit" class="text-blue-600 hover:underline text-xs">
                            <?= $canEdit ? 'Edit' : 'View' ?>
                        </a>
                        <?php if ($canDelete): ?>
                            <form method="POST" action="/transactions/<?= $row['id'] ?>/delete" class="inline"
                                  onsubmit="return confirm('Delete this transaction?');">
                                <?= \App\Core\Csrf::field() ?>
                                <button class="text-red-600 hover:underline text-xs">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($rows)): ?>
                <tr><td colspan="8" class="px-4 py-8 text-center text-slate-400">No transactions found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2 mt-4 text-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <button type="button" @click="page = <?= $p ?>; search()"
                    class="px-3 py-1 rounded <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">
                <?= $p ?>
            </button>
        <?php endfor; ?>
    </div>
<?php endif; ?>
