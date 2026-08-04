<?php
/** @var array $categories */
/** @var string|null $success */
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\View;
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6">Categories</h1>

<?php if ($success): ?><div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3"><?= View::e($success) ?></div><?php endif; ?>
<?php if ($error): ?><div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3"><?= View::e($error) ?></div><?php endif; ?>

<form method="POST" action="/categories" class="bg-white rounded-xl border border-slate-200 p-4 mb-6 flex gap-3 max-w-xl">
    <?= Csrf::field() ?>
    <select name="type" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
        <option value="income">Income</option>
        <option value="expense">Expense</option>
    </select>
    <input type="text" name="name" placeholder="Category name" required class="flex-1 rounded-lg border border-slate-300 px-3 py-2 text-sm">
    <button class="rounded-lg bg-blue-600 text-white text-sm px-4 py-2 hover:bg-blue-700">Add</button>
</form>

<div class="grid md:grid-cols-2 gap-6">
    <?php foreach (['income' => 'Income categories', 'expense' => 'Expense categories'] as $type => $label): ?>
        <div class="bg-white rounded-xl border border-slate-200 p-4">
            <h2 class="text-sm font-semibold text-slate-700 mb-3"><?= $label ?></h2>
            <ul class="divide-y divide-slate-100 text-sm">
                <?php foreach ($categories as $category): if ($category['type'] !== $type) continue; ?>
                    <li class="py-2 flex items-center justify-between">
                        <span class="<?= $category['is_active'] ? 'text-slate-800' : 'text-slate-400 line-through' ?>">
                            <?= View::e($category['name']) ?>
                        </span>
                        <form method="POST" action="/categories/<?= $category['id'] ?>/toggle-active">
                            <?= Csrf::field() ?>
                            <button class="text-xs text-blue-600 hover:underline">
                                <?= $category['is_active'] ? 'Deactivate' : 'Activate' ?>
                            </button>
                        </form>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endforeach; ?>
</div>
