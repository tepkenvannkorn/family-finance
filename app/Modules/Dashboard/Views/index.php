<?php
/** @var array $layout */
/** @var string $currencyDisplay */
/** @var int|null $scopedUserId */
/** @var string $name */
use App\Core\View;
?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<!-- <div x-data="dashboard()" x-init="init()"> -->
<div x-data="dashboard()">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-xl font-semibold text-slate-800">Welcome, <?= View::e($name) ?></h1>
            <p class="text-sm text-slate-500">Family financial overview</p>
        </div>
        <div class="flex items-center gap-2">
            <!-- <select x-model="currency" @change="loadData()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm"> -->
            <select :value="currency" @change="currency = $event.target.value; loadData();" class="rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="original">Original currency</option>
                <option value="USD">USD only</option>
                <option value="KHR">KHR only</option>
            </select>
            <button @click="toggleEdit()" class="rounded-lg border border-slate-300 text-sm px-3 py-2 hover:bg-slate-50">
                <span x-text="editMode ? 'Done' : 'Customize layout'"></span>
            </button>
            <form method="POST" action="/dashboard/layout/reset" x-show="editMode">
                <?= \App\Core\Csrf::field() ?>
                <button class="rounded-lg border border-slate-300 text-sm px-3 py-2 hover:bg-slate-50">Reset layout</button>
            </form>
        </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Total income</p>
            <p class="text-2xl font-semibold text-green-700" x-text="fmt(summary.income)"></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Total expense</p>
            <p class="text-2xl font-semibold text-red-700" x-text="fmt(summary.expense)"></p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-5">
            <p class="text-sm text-slate-500">Balance</p>
            <p class="text-2xl font-semibold" :class="balancePositive ? 'text-green-700' : 'text-red-700'" x-text="fmt(summary.balance)"></p>
        </div>
    </div>

    <div id="widget-grid" class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <template x-for="widget in visibleWidgets" :key="widget.id">
            <div class="bg-white rounded-xl border border-slate-200 p-5"
                 :data-id="widget.id"
                 :class="{'md:col-span-2': widget.size === 'lg'}">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-sm font-semibold text-slate-700" x-text="widgetTitle(widget.id)"></h3>
                    <div x-show="editMode" class="flex items-center gap-2 text-xs">
                        <button @click="cycleSize(widget)" class="text-slate-400 hover:text-slate-700">Resize</button>
                        <button @click="hide(widget)" class="text-slate-400 hover:text-red-600">Hide</button>
                        <span class="drag-handle cursor-move text-slate-300">⠿</span>
                    </div>
                </div>

                <canvas x-show="['income_vs_expense','monthly_trend','weekly_trend','expense_categories','currency_breakdown'].includes(widget.id)"
                        :id="'chart-' + widget.id" height="180"></canvas>

                <div x-show="widget.id === 'recent_transactions'" class="divide-y divide-slate-100 text-sm">
                    <template x-for="t in recent" :key="t.id">
                        <div class="py-2 flex justify-between">
                            <div>
                                <p x-text="t.description" class="text-slate-800"></p>
                                <p x-text="t.transaction_date + ' · ' + t.category_name" class="text-xs text-slate-400"></p>
                            </div>
                            <p :class="t.type === 'income' ? 'text-green-700' : 'text-red-700'"
                               x-text="(t.type === 'income' ? '+' : '-') + t.amount + ' ' + t.currency"></p>
                        </div>
                    </template>
                </div>
            </div>
        </template>
    </div>

    <div x-show="editMode" class="mt-4 flex flex-wrap gap-2">
        <template x-for="widget in hiddenWidgets" :key="widget.id">
            <button @click="show(widget)" class="text-xs rounded-full border border-slate-300 px-3 py-1 text-slate-600 hover:bg-slate-50">
                + <span x-text="widgetTitle(widget.id)"></span>
            </button>
        </template>
    </div>
</div>

<script>
function dashboard() {
    return {
        currency: <?= json_encode($currencyDisplay) ?>,
        editMode: false,
        summary: { income: '0', expense: '0', balance: '0' },
        recent: [],

        charts: {},
        layout: <?= json_encode($layout) ?>,
        titles: {
            balance: 'Balance', income_vs_expense: 'Income vs Expense', monthly_trend: 'Monthly Trend',
            weekly_trend: 'Weekly Trend', expense_categories: 'Expense Categories',
            currency_breakdown: 'Currency Breakdown', recent_transactions: 'Recent Transactions',
        },
        get balancePositive() { return parseFloat(this.summary.balance) >= 0; },
        get visibleWidgets() { return this.layout.widgets.filter(w => typeof w === 'string' ? true : w.visible !== false).map(this.normalize); },
        get hiddenWidgets() { return (this.layout.hidden || []).map(this.normalize); },
        normalize(w) { return typeof w === 'string' ? { id: w, size: 'md', visible: true } : w; },
        widgetTitle(id) { return this.titles[id] || id; },
        fmt(v) { return Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }); },
        init() {
            this.layout.widgets = (this.layout.widgets || []).map(this.normalize);
            this.layout.hidden = (this.layout.hidden || []).map(this.normalize);
            this.loadData();
            // this.$nextTick(() => this.enableDragSort()); <-- Old
            this.$watch('editMode', value => {
                if (value) {
                    this.$nextTick(() => this.enableDragSort());
                } else {
                    this.destroyDragSort();
                }
            });
        },
        toggleEdit() { this.editMode = !this.editMode; },
        cycleSize(widget) {
            widget.size = widget.size === 'md' ? 'lg' : 'md';
            this.saveLayout();
        },
        hide(widget) {
            this.layout.widgets = this.layout.widgets.filter(w => w.id !== widget.id);
            widget.visible = false;
            this.layout.hidden.push(widget);
            this.saveLayout();
        },
        show(widget) {
            this.layout.hidden = this.layout.hidden.filter(w => w.id !== widget.id);
            widget.visible = true;
            this.layout.widgets.push(widget);
            this.saveLayout();
            this.$nextTick(() => this.renderCharts());
        },

        sortable: null,

        enableDragSort() {
            // console.log("enableDragSort");
            if (this.sortable) return;

            const grid = document.getElementById('widget-grid');
            if (!grid || !window.Sortable) return;

            // this.sortable = Sortable.create(grid, {
            //     handle: '.drag-handle',
            //     animation: 150,

            //     onEnd: (evt) => {
            //         const moved = this.layout.widgets.splice(evt.oldIndex, 1)[0];
            //         this.layout.widgets.splice(evt.newIndex, 0, moved);

            //         this.saveLayout();
            //     }
            // });
            this.sortable = Sortable.create(grid, {
                handle: '.drag-handle',
                animation: 0,
                
                onEnd: (evt) => {
                    // console.log('drag ended');
                }
            });
        },

        destroyDragSort() {
            if (this.sortable) {
                this.sortable.destroy();
                this.sortable = null;
            }
        },

        // enableDragSort() {
        //     const grid = document.getElementById('widget-grid');
        //     if (!grid || !window.Sortable) return;
            
        //     Sortable.create(grid, {
        //         handle: '.drag-handle',
        //         animation: 150,
        //         onEnd: () => {
        //             const order = [...grid.children].map(el => el.dataset.id);
        //             this.layout.widgets.sort((a, b) => order.indexOf(a.id) - order.indexOf(b.id));
        //             this.saveLayout();
        //         }
        //     });
        // },

        saveLayout() {
            fetch('/dashboard/layout', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(this.layout)
            });
        },
        loadData() {
            // console.count("loadData");
            // console.trace("loadData");

            fetch('/dashboard/data?currency=' + this.currency)
                .then(r => r.json())
                .then(data => {
                    // console.log(data);
                    
                    this.summary = data.summary;
                    this.recent = data.recent_transactions;
                    // this.$nextTick(() => this.renderCharts(data)); --> old

                    this.$nextTick(() => {
                        requestAnimationFrame(() => {
                            requestAnimationFrame(() => {
                                this.renderCharts(data);
                            });
                        });
                    });
                });
        },
        renderCharts(data) {
            Object.values(this.charts).forEach(chart => {
                try {
                    chart.destroy();
                } catch (e) {
                    console.warn(e);
                }
            });
            this.charts = {};

            const byPeriod = (rows, typeFilter) => {
                const grouped = {};
                rows.filter(r => !typeFilter || r.type === typeFilter).forEach(r => {
                    grouped[r.period] = (grouped[r.period] || 0) + parseFloat(r.total);
                });
                return grouped;
            };

            const mk = (id, config) => {
                const canvas = document.getElementById(`chart-${id}`);
                if (!canvas) return;

                const ctx = canvas.getContext("2d");
                if (!ctx) return;

                this.charts[id] = new Chart(ctx, config);
            };

            if (data.income_vs_expense) {
                const income = byPeriod(data.income_vs_expense, 'income');
                const expense = byPeriod(data.income_vs_expense, 'expense');
                const labels = [...new Set([...Object.keys(income), ...Object.keys(expense)])].sort();
                mk('income_vs_expense', { type: 'bar', data: { labels, datasets: [
                    { label: 'Income', data: labels.map(l => income[l] || 0), backgroundColor: '#16a34a' },
                    { label: 'Expense', data: labels.map(l => expense[l] || 0), backgroundColor: '#dc2626' },
                ]}});
                mk('monthly_trend', { type: 'line', data: { labels, datasets: [
                    { label: 'Income', data: labels.map(l => income[l] || 0), borderColor: '#16a34a', tension: 0.3 },
                    { label: 'Expense', data: labels.map(l => expense[l] || 0), borderColor: '#dc2626', tension: 0.3 },
                ]}});
            }

            if (data.weekly_trend) {
                const income = byPeriod(data.weekly_trend, 'income');
                const expense = byPeriod(data.weekly_trend, 'expense');
                const labels = [...new Set([...Object.keys(income), ...Object.keys(expense)])].sort();
                mk('weekly_trend', { type: 'line', data: { labels, datasets: [
                    { label: 'Income', data: labels.map(l => income[l] || 0), borderColor: '#16a34a', tension: 0.3 },
                    { label: 'Expense', data: labels.map(l => expense[l] || 0), borderColor: '#dc2626', tension: 0.3 },
                ]}});
            }

            if (data.expense_by_category) {
                const totals = {};
                data.expense_by_category.forEach(r => { totals[r.category] = (totals[r.category] || 0) + parseFloat(r.total); });
                mk('expense_categories', { type: 'doughnut', data: {
                    labels: Object.keys(totals),
                    datasets: [{ data: Object.values(totals), backgroundColor: ['#2563eb','#dc2626','#16a34a','#f59e0b','#7c3aed','#0891b2','#db2777','#65a30d','#ea580c','#4b5563'] }]
                }});
            }

            if (data.summary && data.summary.by_currency) {
                const totals = {};
                data.summary.by_currency.forEach(r => { totals[r.currency] = (totals[r.currency] || 0) + parseFloat(r.total); });
                mk('currency_breakdown', { type: 'pie', data: {
                    labels: Object.keys(totals), datasets: [{ data: Object.values(totals), backgroundColor: ['#2563eb', '#f59e0b'] }]
                }});
            }
        }
    };
}
</script>
