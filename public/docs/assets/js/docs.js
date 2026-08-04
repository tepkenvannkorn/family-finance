(function () {
    const stored = localStorage.getItem('docs-theme');
    if (stored) document.documentElement.setAttribute('data-theme', stored);

    document.addEventListener('DOMContentLoaded', function () {
        const toggle = document.getElementById('theme-toggle');
        if (toggle) {
            toggle.addEventListener('click', function () {
                const current = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', current);
                localStorage.setItem('docs-theme', current);
            });
        }

        const search = document.getElementById('doc-search');
        if (search) {
            search.addEventListener('input', function () {
                const term = search.value.toLowerCase();
                document.querySelectorAll('.sidebar a').forEach(function (link) {
                    const matches = link.textContent.toLowerCase().includes(term);
                    link.classList.toggle('hidden', term.length > 0 && !matches);
                });
            });
        }
    });
})();
