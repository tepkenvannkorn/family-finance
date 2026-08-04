<?php
/** @var bool $maintenanceMode */
/** @var string|null $success */
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\View;
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6">Backup & Maintenance</h1>

<?php if ($success): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3"><?= View::e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3"><?= View::e($error) ?></div><?php endif; ?>

<div class="grid md:grid-cols-2 gap-6 max-w-3xl">
    <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-3">
        <h2 class="text-sm font-semibold text-slate-700">Export</h2>
        <a href="/backup/export/database" class="block rounded-lg border border-slate-300 text-sm px-4 py-2 text-center hover:bg-slate-50">
            Download full database backup (.sql)
        </a>
        <a href="/backup/export/transactions" class="block rounded-lg border border-slate-300 text-sm px-4 py-2 text-center hover:bg-slate-50">
            Download all transactions (.csv)
        </a>
        <p class="text-xs text-slate-400">Database export requires the <code>mysqldump</code> binary to be available on the server.</p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6 space-y-3">
        <h2 class="text-sm font-semibold text-slate-700">Maintenance</h2>
        <form method="POST" action="/backup/clear-cache">
            <?= Csrf::field() ?>
            <button class="w-full rounded-lg border border-slate-300 text-sm px-4 py-2 hover:bg-slate-50">Clear settings cache</button>
        </form>
        <form method="POST" action="/backup/toggle-maintenance">
            <?= Csrf::field() ?>
            <button class="w-full rounded-lg text-sm px-4 py-2 <?= $maintenanceMode ? 'bg-amber-600 text-white hover:bg-amber-700' : 'border border-slate-300 hover:bg-slate-50' ?>">
                <?= $maintenanceMode ? 'Disable maintenance mode' : 'Enable maintenance mode' ?>
            </button>
        </form>
        <?php if ($maintenanceMode): ?>
            <p class="text-xs text-amber-600">Maintenance mode is ON — non-admin users currently see a maintenance page instead of the app.</p>
        <?php endif; ?>
        <p class="text-xs text-slate-400">
            <a href="/audit-logs" class="text-blue-600 hover:underline">View audit logs</a> for a full history of system activity.
        </p>
    </div>
</div>
