<?php
/** @var \App\Models\Transaction|null $transaction */
/** @var \App\Models\Attachment[] $attachments */
/** @var \App\Models\Category[] $categories */
/** @var string[] $errors */
/** @var array $old */
/** @var bool $canEdit */
use App\Core\Csrf;
use App\Core\View;

$isEdit = $transaction !== null;
$action = $isEdit ? "/transactions/{$transaction->id}" : '/transactions';
$val = fn (string $key, $default = '') => $old[$key] ?? ($transaction?->{$key} ?? $default);
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6"><?= $isEdit ? ($canEdit ? 'Edit transaction' : 'View transaction') : 'Add transaction' ?></h1>

<?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3 max-w-2xl">
        <?php foreach ($errors as $error): ?><p><?= View::e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- <form method="POST" action="<?= $action ?>" enctype="multipart/form-data"
      class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6 space-y-4"
      x-data="{ type: <?= json_encode($val('type', 'expense')) ?> }">
    <?= Csrf::field() ?> -->
<form method="POST" action="<?= $action ?>" enctype="multipart/form-data"
      class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6 space-y-4"
      x-data='{ type: <?= json_encode($val('type', 'expense')) ?> }'>
    <?= Csrf::field() ?>
    <fieldset <?= $isEdit && !$canEdit ? 'disabled' : '' ?> class="space-y-4">

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Type</label>
            <select name="type" x-model="type" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="income" <?= $val('type', 'expense') === 'income' ? 'selected' : '' ?>>Income</option>
                <option value="expense" <?= $val('type', 'expense') === 'expense' ? 'selected' : '' ?>>Expense</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Currency</label>
            <select name="currency" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="USD" <?= $val('currency', 'USD') === 'USD' ? 'selected' : '' ?>>USD</option>
                <option value="KHR" <?= $val('currency', 'USD') === 'KHR' ? 'selected' : '' ?>>KHR</option>
            </select>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
            <input type="number" step="0.01" min="0.01" name="amount" required value="<?= View::e((string) $val('amount')) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
            <select name="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <?php foreach ($categories as $category): ?>
                    <option value="<?= $category->id ?>"
                        x-show="type === '<?= $category->type ?>'"
                        <?= (int) $val('category_id') === $category->id ? 'selected' : '' ?>>
                        <?= View::e($category->name) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
        <input type="text" name="description" required value="<?= View::e((string) $val('description')) ?>"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
    </div>

    <div class="grid grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Date</label>
            <input type="date" name="transaction_date" required
                   value="<?= View::e((string) $val('transaction_date', date('Y-m-d'))) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Time</label>
            <input type="time" name="transaction_time" step="1"
                   value="<?= View::e((string) $val('transaction_time', date('H:i:s'))) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Notes (optional)</label>
        <textarea name="notes" rows="2" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"><?= View::e((string) $val('notes')) ?></textarea>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Attach receipt/invoice/bill (JPG, PNG, or PDF, optional)</label>
        <input type="file" name="attachment" accept=".jpg,.jpeg,.png,.pdf"
               class="w-full text-sm text-slate-600">
    </div>

    <div class="flex gap-3 pt-2">
        <?php if (!$isEdit || $canEdit): ?>
            <button type="submit" class="rounded-lg bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
                <?= $isEdit ? 'Save changes' : 'Add transaction' ?>
            </button>
        <?php endif; ?>
        <a href="/transactions" class="rounded-lg border border-slate-300 text-slate-600 text-sm font-medium px-4 py-2 hover:bg-slate-50">
            <?= $isEdit && !$canEdit ? 'Back' : 'Cancel' ?>
        </a>
    </div>
    </fieldset>
</form>

<?php if ($isEdit && !empty($attachments)): ?>
    <div class="max-w-2xl bg-white rounded-xl border border-slate-200 p-6 mt-4">
        <h2 class="text-sm font-semibold text-slate-700 mb-3">Attachments</h2>
        <ul class="space-y-2">
            <?php foreach ($attachments as $attachment): ?>
                <li class="flex items-center justify-between text-sm">
                    <a href="/transactions/<?= $transaction->id ?>/attachments/<?= $attachment->id ?>"
                       target="_blank" class="text-blue-600 hover:underline">
                        <?= View::e($attachment->originalFilename) ?>
                    </a>
                    <?php if ($canEdit): ?>
                        <form method="POST" action="/transactions/<?= $transaction->id ?>/attachments/<?= $attachment->id ?>/delete"
                              onsubmit="return confirm('Remove this attachment?');">
                            <?= Csrf::field() ?>
                            <button class="text-red-600 hover:underline text-xs">Remove</button>
                        </form>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
