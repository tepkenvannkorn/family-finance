<?php
/** @var \App\Models\User[] $users */
/** @var int $total */
/** @var int $page */
/** @var int $perPage */
/** @var string $keyword */
/** @var int $currentUserId */
/** @var string|null $success */
/** @var string|null $error */
use App\Core\Csrf;
use App\Core\View;

$totalPages = max(1, (int) ceil($total / $perPage));
?>
<div class="flex items-center justify-between mb-6">
    <h1 class="text-xl font-semibold text-slate-800">Users</h1>
    <a href="/users/create" class="rounded-lg bg-blue-600 text-white text-sm font-medium px-4 py-2 hover:bg-blue-700">
        + Add user
    </a>
</div>

<?php if ($success): ?>
    <div class="mb-4 rounded-lg bg-green-50 border border-green-200 text-green-700 text-sm p-3"><?= View::e($success) ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3"><?= View::e($error) ?></div>
<?php endif; ?>

<form method="GET" action="/users" class="mb-4">
    <input type="text" name="q" value="<?= View::e($keyword) ?>" placeholder="Search by name or email…"
           class="w-full max-w-sm rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
</form>

<div class="bg-white rounded-xl border border-slate-200 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-left">
            <tr>
                <th class="px-4 py-3 font-medium">Name</th>
                <th class="px-4 py-3 font-medium">Email</th>
                <th class="px-4 py-3 font-medium">Role</th>
                <th class="px-4 py-3 font-medium">Status</th>
                <th class="px-4 py-3 font-medium text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            <?php foreach ($users as $user): ?>
                <tr>
                    <td class="px-4 py-3 text-slate-800">
                        <?= View::e($user->name) ?>
                        <?php if ($user->id === $currentUserId): ?>
                            <span class="text-xs text-slate-400">(you)</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-slate-600"><?= View::e($user->email) ?></td>
                    <td class="px-4 py-3">
                        <span class="inline-block rounded-full px-2 py-0.5 text-xs bg-slate-100 text-slate-600">
                            <?= View::e($user->roleName) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3">
                        <?php if ($user->isLocked()): ?>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs bg-amber-100 text-amber-700">Locked</span>
                        <?php elseif ($user->isActive): ?>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs bg-green-100 text-green-700">Active</span>
                        <?php else: ?>
                            <span class="inline-block rounded-full px-2 py-0.5 text-xs bg-slate-200 text-slate-500">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                        <a href="/users/<?= $user->id ?>/edit" class="text-blue-600 hover:underline text-xs">Edit</a>

                        <?php if ($user->isLocked()): ?>
                            <form method="POST" action="/users/<?= $user->id ?>/unlock" class="inline">
                                <?= Csrf::field() ?>
                                <button class="text-amber-700 hover:underline text-xs">Unlock</button>
                            </form>
                        <?php endif; ?>

                        <?php if ($user->id !== $currentUserId): ?>
                            <form method="POST" action="/users/<?= $user->id ?>/toggle-active" class="inline">
                                <?= Csrf::field() ?>
                                <button class="text-slate-500 hover:underline text-xs">
                                    <?= $user->isActive ? 'Deactivate' : 'Activate' ?>
                                </button>
                            </form>
                            <form method="POST" action="/users/<?= $user->id ?>/delete" class="inline"
                                  onsubmit="return confirm('Permanently delete this user? This cannot be undone.');">
                                <?= Csrf::field() ?>
                                <button class="text-red-600 hover:underline text-xs">Delete</button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($users)): ?>
                <tr><td colspan="5" class="px-4 py-8 text-center text-slate-400">No users found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-2 mt-4 text-sm">
        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
            <a href="/users?q=<?= urlencode($keyword) ?>&page=<?= $p ?>"
               class="px-3 py-1 rounded <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-white border border-slate-200 text-slate-600' ?>">
                <?= $p ?>
            </a>
        <?php endfor; ?>
    </div>
<?php endif; ?>
