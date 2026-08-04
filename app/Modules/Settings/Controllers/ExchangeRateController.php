<?php

declare(strict_types=1);

namespace App\Modules\Settings\Controllers;

use App\Core\AuditLogger;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\View;
use App\Models\ExchangeRate;
use App\Modules\Settings\Services\ExchangeRateService;

final class ExchangeRateController
{
    public function index(Request $request): void
    {
        echo View::render('Settings::exchange-rates', [
            'latest' => ExchangeRate::latest(),
            'history' => ExchangeRate::history(30),
            'success' => Session::pull('success'),
            'error' => Session::pull('error'),
        ]);
    }

    public function setManual(Request $request): void
    {
        try {
            $rate = (new ExchangeRateService())->setManualRate(
                (string) $request->input('rate', ''),
                (int) Session::get('user_id')
            );
            AuditLogger::log((int) Session::get('user_id'), 'exchange_rate.manual_set', 'exchange_rate', $rate->id, ['rate' => $rate->rate]);
            Session::flash('success', 'Exchange rate updated.');
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect('/settings/exchange-rates');
    }

    public function fetchNow(Request $request): void
    {
        try {
            $rate = (new ExchangeRateService())->fetchFromApi();
            AuditLogger::log((int) Session::get('user_id'), 'exchange_rate.api_fetch', 'exchange_rate', $rate->id, ['rate' => $rate->rate]);
            Session::flash('success', "Fetched latest rate: {$rate->rate}");
        } catch (\RuntimeException $e) {
            Session::flash('error', $e->getMessage());
        }

        Response::redirect('/settings/exchange-rates');
    }
}
