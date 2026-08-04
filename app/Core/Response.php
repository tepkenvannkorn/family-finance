<?php

declare(strict_types=1);

namespace App\Core;

final class Response
{
    public static function redirect(string $to): never
    {
        header('Location: ' . $to, true, 302);
        exit;
    }

    public static function json(array $data, int $status = 200): never
    {
        http_response_code($status);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    public static function forbidden(): never
    {
        http_response_code(403);
        echo '403 — Forbidden';
        exit;
    }

    public static function notFound(): never
    {
        http_response_code(404);
        echo '404 — Not Found';
        exit;
    }
}
