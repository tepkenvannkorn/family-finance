<?php
use App\Core\Session;

$current = strtok($_SERVER['REQUEST_URI'], '?');

$active = function (string $url) use ($current): string {
    return $current === $url
        ? 'bg-blue-50 text-blue-700 font-medium'
        : 'text-slate-600 hover:bg-slate-100';
};

?>

<aside
    class="bg-white border-r border-slate-200
           w-64 h-screen fixed left-0 top-0
           flex flex-col">

    <div class="h-16 flex items-center px-6 border-b">
        <h1 class="text-xl font-bold text-slate-800">
            VK Finance
        </h1>
    </div>

    <nav class="flex-1 p-4 space-y-1">

        <a href="/dashboard"
           class="flex items-center px-3 py-2 rounded-lg <?= $active('/dashboard') ?>">
            Dashboard
        </a>

        <a href="/transactions"
           class="flex items-center px-3 py-2 rounded-lg <?= $active('/transactions') ?>">
            Transactions
        </a>

        <a href="/reports"
           class="flex items-center px-3 py-2 rounded-lg <?= $active('/reports') ?>">
            Reports
        </a>

        <?php if (Session::get('role_name') === 'admin'): ?>

            <a href="/users"
               class="flex items-center px-3 py-2 rounded-lg <?= $active('/users') ?>">
                Users
            </a>

            <a href="/settings"
               class="flex items-center px-3 py-2 rounded-lg <?= $active('/settings') ?>">
                Settings
            </a>

        <?php endif; ?>

        <a href="/profile"
           class="flex items-center px-3 py-2 rounded-lg <?= $active('/profile') ?>">
            Profile
        </a>

    </nav>

    <div class="border-t p-4">

        <form method="POST" action="/logout">
            <?= \App\Core\Csrf::field() ?>

            <button
                class="w-full rounded-lg bg-red-50
                       py-2 text-red-600 hover:bg-red-100">
                Log out
            </button>

        </form>

    </div>

</aside>