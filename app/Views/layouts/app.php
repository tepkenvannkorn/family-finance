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
    <link rel="stylesheet" href="/assets/css/app.min.css">
    <script src="/assets/js/storage.js"></script>
    <script defer src="/assets/js/alpine.min.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', () => navigator.serviceWorker.register('/service-worker.js'));
        }
        document.addEventListener('alpine:init', () => {
            Alpine.store('app', {
                sidebarOpen: false,
                sidebarMini: false,
                currency: '<?= View::e($currencyDisplay ?? 'original') ?>',
                pageTitle: '',
                editMode: false,
                notifications: 0,
                online: navigator.onLine
            });
        });
    </script>
</head>
<body class="bg-slate-50" x-data>

    <!-- Dark overlay -->

    <div
        x-cloak
        x-show="$store.app.sidebarOpen"
        x-transition.opacity
        @click="$store.app.sidebarOpen=false"
        class="fixed inset-0 bg-black/40 z-40 lg:hidden">

    </div>

    <!-- Mobile sidebar -->

    <div
        x-cloak
        class="fixed inset-y-0 left-0 z-50
               w-64
               transform transition-transform
               lg:hidden"

        :class="$store.app.sidebarOpen
            ? 'translate-x-0'
            : '-translate-x-full'">

        <?php include __DIR__.'/../partials/sidebar.php'; ?>

    </div>

    <!-- Desktop sidebar -->

    <div class="hidden lg:block">

        <?php include __DIR__.'/../partials/sidebar.php'; ?>

    </div>

    <!-- Content -->

    <div class="lg:ml-64 min-h-screen">

        <?php include __DIR__.'/../partials/topbar.php'; ?>

        <main class="p-6">

            <?= $content ?>

        </main>

    </div>

</body>
</html>
