<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Deliberately minimal — no template compiler. Views are plain PHP files
 * under app/Views or app/Modules/<Module>/Views. Output is escaped by
 * default via View::e(); anything that must render raw HTML calls
 * View::raw() explicitly, so unescaped output is always a visible,
 * deliberate choice rather than an accident (spec §2: XSS prevention).
 */
final class View
{
    public static function render(string $viewPath, array $data = [], ?string $layout = 'layouts/app'): string
    {
        $content = self::renderFile($viewPath, $data);

        if ($layout === null) {
            return $content;
        }

        return self::renderFile($layout, array_merge($data, ['content' => $content]));
    }

    public static function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }

    public static function raw(string $value): string
    {
        return $value;
    }

    private static function renderFile(string $viewPath, array $data): string
    {
        $file = self::resolve($viewPath);
        if (!is_file($file)) {
            throw new \RuntimeException("View not found: {$viewPath}");
        }

        extract($data, EXTR_SKIP);
        ob_start();
        include $file;
        return (string) ob_get_clean();
    }

    private static function resolve(string $viewPath): string
    {
        $root = dirname(__DIR__); // app/
        // Module views: "Auth::login" -> app/Modules/Auth/Views/login.php
        if (str_contains($viewPath, '::')) {
            [$module, $view] = explode('::', $viewPath, 2);
            return "{$root}/Modules/{$module}/Views/{$view}.php";
        }
        // Shared views: "layouts/app" -> app/Views/layouts/app.php
        return "{$root}/Views/{$viewPath}.php";
    }
}
