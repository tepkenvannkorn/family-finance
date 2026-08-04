<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Thin wrapper over PHP superglobals. Input helpers trim whitespace but
 * deliberately do NOT strip/encode here — output escaping (View::e()) is
 * where XSS is actually prevented; sanitizing on input as well tends to
 * corrupt legitimate data (e.g. a note that contains "<3").
 */
final class Request
{
    public readonly string $method;
    public readonly string $path;
    private array $query;
    private array $body;
    private array $files;

    public function __construct()
    {
        $this->method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
        $this->path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
        $this->query = $_GET;
        $this->files = $_FILES;

        if (str_contains($_SERVER['CONTENT_TYPE'] ?? '', 'application/json')) {
            $this->body = json_decode(file_get_contents('php://input') ?: '[]', true) ?? [];
        } else {
            $this->body = $_POST;
        }
    }

    public function input(string $key, mixed $default = null): mixed
    {
        $value = $this->body[$key] ?? $this->query[$key] ?? $default;
        return is_string($value) ? trim($value) : $value;
    }

    public function all(): array
    {
        return array_merge($this->query, $this->body);
    }

    public function isPost(): bool
    {
        return $this->method === 'POST';
    }

    public function ip(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function userAgent(): string
    {
        return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
    }
}
