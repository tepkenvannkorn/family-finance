<?php
/** @var \App\Models\ExchangeRate|null $latest */
/** @var \App\Models\ExchangeRate[] $history */
/** @var string|null $success */
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\View;
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6">Exchange Rates</h1>

<?php if ($success): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3"><?= View::e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3"><?= View::e($error) ?></div><?php endif; ?>

<div class="grid md:grid-cols-2 gap-6 max-w-3xl">
    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Current rate</h2>
        <?php if ($latest): ?>
            <p class="text-2xl font-semibold text-slate-800">1 USD = <?= number_format((float) $latest->rate, 2) ?> KHR</p>
            <p class="text-xs text-slate-400 mt-1">
                Source: <?= View::e($latest->source) ?> · Last updated: <?= View::e($latest->fetchedAt) ?>
            </p>
        <?php else: ?>
            <p class="text-slate-400">No rate set yet.</p>
        <?php endif; ?>

        <form method="POST" action="/settings/exchange-rates/manual" class="mt-4 flex gap-2">
            <?= Csrf::field() ?>
            <input type="number" step="0.000001" name="rate" placeholder="e.g. 4100.00" required
                   class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <button class="rounded-lg bg-blue-600 text-white text-sm px-4 py-2 hover:bg-blue-700">Set manually</button>
        </form>

        <form method="POST" action="/settings/exchange-rates/fetch" class="mt-2">
            <?= Csrf::field() ?>
            <button class="rounded-lg border border-slate-300 text-sm px-4 py-2 hover:bg-slate-50 w-full">
                Fetch latest rate now
            </button>
        </form>
        <p class="text-xs text-slate-400 mt-2">
            Automatic scheduled sync is configured under Settings → Currency (sync interval: every login / daily / weekly).
        </p>
    </div>

    <div class="bg-white rounded-xl border border-slate-200 p-6">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">History</h2>
        <ul class="divide-y divide-slate-100 text-sm max-h-80 overflow-y-auto">
            <?php foreach ($history as $entry): ?>
                <li class="py-2 flex justify-between">
                    <span><?= number_format((float) $entry->rate, 2) ?> KHR</span>
                    <span class="text-slate-400 text-xs"><?= View::e($entry->source) ?> · <?= View::e($entry->fetchedAt) ?></span>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</div>
