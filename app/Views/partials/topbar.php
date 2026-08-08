<?php

use App\Core\Session;
?>

<header
    class="sticky top-0 z-30
           h-16 bg-white border-b border-slate-200
           flex items-center justify-between
           px-6">

    <!-- Left -->

    <div class="flex items-center gap-4">

        <!-- Mobile hamburger -->
        <button
            @click="sidebarOpen = !sidebarOpen"
            class="lg:hidden rounded-lg p-2 hover:bg-slate-100">

            <svg
                xmlns="http://www.w3.org/2000/svg"
                class="w-6 h-6"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor">

                <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M4 6h16M4 12h16M4 18h16"/>

            </svg>

        </button>

        <h2 class="font-semibold text-slate-700">
            VK Finance
        </h2>

    </div>

    <!-- Right -->

    <div class="flex items-center gap-4">

        <span class="text-sm text-slate-500">

            <?= htmlspecialchars((string) Session::get('name')) ?>

        </span>

    </div>

</header>