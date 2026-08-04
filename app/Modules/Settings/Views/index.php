<?php
/** @var string $group */
/** @var string[] $groups */
/** @var array $fields */
/** @var array $values */
/** @var string|null $success */
use App\Core\Csrf;
use App\Core\View;

$labels = [
    'general' => 'General', 'currency' => 'Currency', 'transaction' => 'Transactions',
    'dashboard' => 'Dashboard', 'user' => 'User & Security', 'appearance' => 'Appearance',
    'notification' => 'Notifications', 'security' => 'Security', 'feature_flags' => 'Feature Toggles',
];
?>
<h1 class="text-xl font-semibold text-slate-800 mb-2">Settings</h1>
<p class="text-sm text-slate-500 mb-6">
    All settings are stored in the database and take effect immediately — no code changes needed.
    <a href="/settings/exchange-rates" class="text-blue-600 hover:underline">Manage exchange rates →</a>
    <a href="/audit-logs" class="text-blue-600 hover:underline ml-3">View audit logs →</a>
    <a href="/backup" class="text-blue-600 hover:underline ml-3">Backup & maintenance →</a>
    <a href="/categories" class="text-blue-600 hover:underline ml-3">Manage categories →</a>
</p>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3"><?= View::e($success) ?></div>
<?php endif; ?>

<div class="flex gap-2 mb-6 flex-wrap">
    <?php foreach ($groups as $g): ?>
        <a href="/settings?group=<?= $g ?>"
           class="text-sm px-3 py-1.5 rounded-lg <?= $g === $group ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">
            <?= View::e($labels[$g] ?? ucfirst($g)) ?>
        </a>
    <?php endforeach; ?>
</div>

<form method="POST" action="/settings/<?= $group ?>" class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6 space-y-4">
    <?= Csrf::field() ?>

    <?php foreach ($fields as $key => $type): ?>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1"><?= View::e(ucwords(str_replace('_', ' ', $key))) ?></label>
            <?php if ($type === 'bool'): ?>
                <label class="flex items-center gap-2 text-sm text-slate-600">
                    <input type="checkbox" name="<?= $key ?>" value="1" <?= !empty($values[$key]) ? 'checked' : '' ?>
                           class="rounded border-slate-300">
                    Enabled
                </label>
            <?php elseif ($type === 'int'): ?>
                <input type="number" name="<?= $key ?>" value="<?= View::e((string) ($values[$key] ?? 0)) ?>"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <?php else: ?>
                <input type="text" name="<?= $key ?>" value="<?= View::e((string) ($values[$key] ?? '')) ?>"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

    <button type="submit" class="rounded-lg bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
        Save <?= View::e($labels[$group] ?? $group) ?> settings
    </button>
</form>
