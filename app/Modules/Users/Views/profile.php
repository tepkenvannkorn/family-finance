<?php
/** @var \App\Models\User $user */
/** @var string[] $errors */
/** @var string|null $success */
use App\Core\Csrf;
use App\Core\SettingsCache;
use App\Core\View;
?>
<h1 class="text-xl font-semibold text-slate-800 mb-6">My profile</h1>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3 max-w-lg"><?= View::e($success) ?></div>
<?php endif; ?>
<?php if (!empty($errors)): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3 max-w-lg">
        <?php foreach ($errors as $error): ?><p><?= View::e($error) ?></p><?php endforeach; ?>
    </div>
<?php endif; ?>

<div class="grid gap-6 max-w-lg">
    <form method="POST" action="/profile" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
        <h2 class="text-sm font-semibold text-slate-700">Account details</h2>
        <?= Csrf::field() ?>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
            <input type="text" name="name" required value="<?= View::e($user->name) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <div>
            <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
            <input type="email" name="email" required value="<?= View::e($user->email) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
        </div>

        <button type="submit" class="rounded-lg bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
            Save changes
        </button>
    </form>

    <?php if (SettingsCache::get('user', 'allow_self_password_reset', true)): ?>
        <form method="POST" action="/profile/password" class="bg-white rounded-xl border border-slate-200 p-6 space-y-4">
            <h2 class="text-sm font-semibold text-slate-700">Change password</h2>
            <?= Csrf::field() ?>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Current password</label>
                <input type="password" name="current_password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">New password</label>
                <input type="password" name="new_password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">Confirm new password</label>
                <input type="password" name="confirm_password" required
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <button type="submit" class="rounded-lg bg-slate-800 text-white text-sm font-medium px-4 py-2 hover:bg-slate-700">
                Change password
            </button>
        </form>
    <?php endif; ?>
</div>
