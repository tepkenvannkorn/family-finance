<?php

declare(strict_types=1);

namespace App\Modules\Reports\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\SettingsCache;
use App\Core\View;
use App\Models\Category;
use App\Modules\Reports\Services\ReportExporter;
use App\Modules\Transactions\Repositories\TransactionRepository;
use App\Modules\Transactions\Services\CurrencyConverter;

final class ReportController
{
    public function index(Request $request): void
    {
        [$dateFrom, $dateTo, $period] = $this->resolvePeriod($request);
        $filters = $this->buildFilters($request, $dateFrom, $dateTo);

        $repo = new TransactionRepository();
        $result = $repo->filtered($filters, 'transaction_date', 'asc', 1, 100000); // reports are unpaginated by design
        $totals = $repo->totalsByCurrency($filters);

        echo View::render('Reports::index', [
            'rows' => $result['rows'],
            'totals' => $totals,
            'period' => $period,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'categories' => Category::active(),
            'roleName' => (string) Session::get('role_name'),
            'query' => $request->all(),
        ]);
    }

    public function exportCsv(Request $request): void
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($request);
        $rows = $this->reportRows($request, $dateFrom, $dateTo);
        (new ReportExporter())->streamCsv($rows, "report_{$dateFrom}_to_{$dateTo}");
    }

    public function exportExcel(Request $request): void
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($request);
        $rows = $this->reportRows($request, $dateFrom, $dateTo);
        (new ReportExporter())->streamExcel($rows, "report_{$dateFrom}_to_{$dateTo}");
    }

    public function exportPdf(Request $request): void
    {
        [$dateFrom, $dateTo] = $this->resolvePeriod($request);
        $rows = $this->reportRows($request, $dateFrom, $dateTo);

        $html = View::render('Reports::pdf', [
            'rows' => $rows, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo,
        ], layout: null);

        (new ReportExporter())->streamPdf($html, "report_{$dateFrom}_to_{$dateTo}");
    }

    private function reportRows(Request $request, string $dateFrom, string $dateTo): array
    {
        $filters = $this->buildFilters($request, $dateFrom, $dateTo);
        $repo = new TransactionRepository();
        return $repo->filtered($filters, 'transaction_date', 'asc', 1, 100000)['rows'];
    }

    private function buildFilters(Request $request, string $dateFrom, string $dateTo): array
    {
        $roleName = (string) Session::get('role_name');
        $filters = array_filter([
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'type' => $request->input('type'),
            'currency' => $request->input('currency'),
            'category_id' => $request->input('category_id') ? (int) $request->input('category_id') : null,
        ], fn ($v) => $v !== null && $v !== '');

        if ($roleName !== 'admin') {
            $filters['user_id'] = (int) Session::get('user_id');
        }

        return $filters;
    }

    /** @return array{0: string, 1: string, 2: string} [dateFrom, dateTo, periodLabel] */
    private function resolvePeriod(Request $request): array
    {
        $period = (string) $request->input('period', 'monthly');
        $today = new \DateTimeImmutable('today');

        return match ($period) {
            'daily' => [$today->format('Y-m-d'), $today->format('Y-m-d'), 'daily'],
            'weekly' => [$today->modify('monday this week')->format('Y-m-d'), $today->modify('sunday this week')->format('Y-m-d'), 'weekly'],
            'yearly' => [$today->modify('first day of january this year')->format('Y-m-d'), $today->modify('last day of december this year')->format('Y-m-d'), 'yearly'],
            'custom' => [
                (string) $request->input('date_from', $today->format('Y-m-d')),
                (string) $request->input('date_to', $today->format('Y-m-d')),
                'custom',
            ],
            default => [$today->modify('first day of this month')->format('Y-m-d'), $today->modify('last day of this month')->format('Y-m-d'), 'monthly'],
        };
    }
}
