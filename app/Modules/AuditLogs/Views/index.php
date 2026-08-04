<?php
/** @var array $logs */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $action */
use App\Core\View;
$totalPages = max(1, (int) ceil($total / $perPage));
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6">Audit Logs</h1>

<form method="GET" action="/audit-logs" class="mb-4">
    <input type="text" name="action" value="<?= View::e($action) ?>" placeholder="Filter by action (e.g. transaction.create)…"
           class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm">
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">When</th>
                <th class="px-4 py-3 font-medium">User</th>
                <th class="px-4 py-3 font-medium">Action</th>
                <th class="px-4 py-3 font-medium">Entity</th>
                <th class="px-4 py-3 font-medium">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td class="px-4 py-3 text-slate-500"><?= View::e($log['created_at']) ?></td>
                    <td class="px-4 py-3 text-slate-700"><?= View::e($log['user_name'] ?? 'System') ?></td>
                    <td class="px-4 py-3"><span class="inline-block rounded-full px-2 py-0.5 text-xs bg-slate-100 text-slate-600"><?= View::e($log['action']) ?></span></td>
                    <td class="px-4 py-3 text-slate-500"><?= View::e(($log['entity_type'] ?? '') . ($log['entity_id'] ? ' #' . $log['entity_id'] : '')) ?></td>
                    <td class="px-4 py-3 text-slate-400 text-xs"><?= View::e($log['ip_address'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($logs)): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No log entries found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2 mt-4 text-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="/audit-logs?action=<?= urlencode($action) ?>&page=<?= $p ?>"
               class="px-3 py-1 rounded <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>"><?= $p ?></a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
