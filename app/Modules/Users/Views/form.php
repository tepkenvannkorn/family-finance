<?php
/** @var \App\Models\User|null $user */
/** @var \App\Models\Role[] $roles */
/** @var string[] $errors */
/** @var array $old */
use App\Core\Csrf;
use App\Core\View;

$isEdit = $user !== null;
$action = $isEdit ? "/users/{$user->id}" : '/users';
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6"><?= $isEdit ? 'Edit user' : 'Add user' ?></h1>

<?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3 max-w-lg">
        <?php foreach ($errors as $error): ?><p><?= View::e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<form method="POST" action="<?= $action ?>" class="max-w-lg bg-white rounded-xl border border-slate-200 p-6 space-y-4">
    <?= Csrf::field() ?>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
        <input type="text" name="name" required
               value="<?= View::e($old['name'] ?? $user?->name ?? '') ?>"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
        <input type="email" name="email" required
               value="<?= View::e($old['email'] ?? $user?->email ?? '') ?>"
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
        <select name="role_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            <?php foreach ($roles as $role): ?>
                <option value="<?= $role->id ?>" <?= (int) ($old['role_id'] ?? $user?->roleId ?? 0) === $role->id ? 'selected' : '' ?>>
                    <?= View::e(ucfirst($role->name)) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">
            <?= $isEdit ? 'New password (leave blank to keep current)' : 'Password' ?>
        </label>
        <input type="password" name="password" <?= $isEdit ? '' : 'required' ?>
               class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
    </div>

    <div class="flex gap-3 pt-2">
        <button type="submit" class="rounded-lg bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
            <?= $isEdit ? 'Save changes' : 'Create user' ?>
        </button>
        <a href="/users" class="rounded-lg border border-slate-300 text-slate-600 text-sm font-medium px-4 py-2 hover:bg-slate-50">
            Cancel
        </a>
    </div>
</form>
