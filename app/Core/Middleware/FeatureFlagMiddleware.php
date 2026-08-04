<?php

declare(strict_types=1);

namespace App\Core\Middleware;

use App\Core\Response;
use App\Core\SettingsCache;

final class FeatureFlagMiddleware
{
    public function __construct(private string $flagKey)
    {
    }

    public function handle(callable $next): void
    {
        if (!SettingsCache::get('feature_flags', $this->flagKey, true)) {
            Response::notFound(); // disabled modules simply don't exist, rather than exposing a "disabled" page
        }
        $next();
    }
}
