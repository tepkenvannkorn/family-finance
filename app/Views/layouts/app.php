<?php
/** @var string $content */
use App\Core\Session;
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? View::e($title) . ' — ' : '' ?>VK Finance</title>
    <link rel="manifest" href="/manifest.json">
    <meta name="theme-color" content="#2563eb">
    <!-- Dev-only CDN build of Tailwind; swapped for the compiled build in Phase 6+. -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'));
        }
    </script>
</head>
<body class="min-h-screen bg-slate-50">
    <nav class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 h-14 flex items-center justify-between">
            <div class="flex items-center gap-6">
                <a href="/dashboard" class="font-semibold text-slate-800">VK Finance</a>
                <a href="/dashboard" class="text-sm text-slate-500 hover:text-slate-800">Dashboard</a>
                <a href="/transactions" class="text-sm text-slate-500 hover:text-slate-800">Transactions</a>
                <a href="/reports" class="text-sm text-slate-500 hover:text-slate-800">Reports</a>
                <?php if (Session::get('role_name') === 'admin'): ?>
                    <a href="/users" class="text-sm text-slate-500 hover:text-slate-800">Users</a>
                    <a href="/settings" class="text-sm text-slate-500 hover:text-slate-800">Settings</a>
                <?php endif; ?>
                <a href="/profile" class="text-sm text-slate-500 hover:text-slate-800">Profile</a>
            </div>
            <form method="POST" action="/logout">
                <?= \App\Core\Csrf::field() ?>
                <button type="submit" class="text-sm text-slate-500 hover:text-slate-800">Log out</button>
            </form>
        </div>
    </nav>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <?= $content ?>
    </main>
</body>
</html>
