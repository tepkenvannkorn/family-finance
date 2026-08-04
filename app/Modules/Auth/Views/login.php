<?php
/** @var array $errors */
/** @var string $old_email */
use App\Core\Csrf;
use App\Core\View;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — VK Finance</title>
    <!-- Dev-only CDN build of Tailwind for now; Phase 6+ swaps this for the compiled
         production stylesheet at /assets/css/app.css per the Phase 1 tech decisions. -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
    <div class="w-full max-w-sm">
        <div class="text-center mb-8">
            <h1 class="text-2xl font-semibold text-slate-800">VK Finance</h1>
            <p class="text-slate-500 text-sm mt-1">Sign in to your family account</p>
        </div>

        <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
            <?php if (!empty($errors)): ?>
                <div class="mb-4 rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm p-3">
                    <?php foreach ($errors as $error): ?>
                        <p><?= View::e($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="/login" class="space-y-4">
                <?= Csrf::field() ?>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" required autofocus
                           value="<?= View::e($old_email) ?>"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password" required
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                </div>

                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 text-sm text-slate-600">
                        <input type="checkbox" name="remember" value="1" class="rounded border-slate-300">
                        Remember me
                    </label>
                </div>

                <button type="submit"
                        class="w-full rounded-lg bg-blue-600 text-white text-sm font-medium py-2.5 hover:bg-blue-700 transition">
                    Sign in
                </button>
            </form>
        </div>

        <p class="text-center text-xs text-slate-400 mt-6">Private family application — not for public use.</p>
    </div>
</body>
</html>
