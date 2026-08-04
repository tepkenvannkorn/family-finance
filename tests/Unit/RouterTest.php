<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Core\Request;
use App\Core\Router;
use PHPUnit\Framework\TestCase;

final class FakeController
{
    public static array $calls = [];

    public function show(Request $request, string $id): void
    {
        self::$calls[] = ['show', $id];
        echo "shown:{$id}";
    }

    public function index(Request $request): void
    {
        self::$calls[] = ['index'];
        echo 'indexed';
    }
}

final class RouterTest extends TestCase
{
    protected function setUp(): void
    {
        FakeController::$calls = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
    }

    public function testMatchesStaticRoute(): void
    {
        $_SERVER['REQUEST_URI'] = '/things';
        $router = new Router();
        $router->get('/things', [FakeController::class, 'index']);

        ob_start();
        $router->dispatch(new Request());
        $output = ob_get_clean();

        $this->assertSame('indexed', $output);
        $this->assertSame([['index']], FakeController::$calls);
    }

    public function testExtractsRouteParameters(): void
    {
        $_SERVER['REQUEST_URI'] = '/things/42';
        $router = new Router();
        $router->get('/things/{id}', [FakeController::class, 'show']);

        ob_start();
        $router->dispatch(new Request());
        $output = ob_get_clean();

        $this->assertSame('shown:42', $output);
        $this->assertSame([['show', '42']], FakeController::$calls);
    }

    public function testOnlyMatchesTheRegisteredMethod(): void
    {
        $_SERVER['REQUEST_URI'] = '/things';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $router = new Router();
        $router->post('/things', [FakeController::class, 'show']); // registered for POST only
        $router->get('/things', [FakeController::class, 'index']); // this one should match a GET request

        ob_start();
        $router->dispatch(new Request());
        $output = ob_get_clean();

        $this->assertSame('indexed', $output);
        $this->assertSame([['index']], FakeController::$calls);
    }
}
