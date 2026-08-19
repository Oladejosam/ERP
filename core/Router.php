<?php
/**
 * Simple MVC router for the ERP application.
 */
declare(strict_types=1);

class Router
{
    public static function route(array $routes): void
    {
        $requestUri = strtok($_SERVER['REQUEST_URI'], '?');
        $path = str_replace('/ERP/public', '', $requestUri);
        $path = $path === '' ? '/' : $path;

        if (isset($routes[$path])) {
            [$controller, $method] = $routes[$path];
            self::dispatch($controller, $method);
            return;
        }

        self::dispatch('HomeController', 'notFound');
    }

    private static function dispatch(string $controller, string $method): void
    {
        require_once APP_ROOT . '/app/Controllers/' . $controller . '.php';
        $instance = new $controller();
        $instance->$method();
    }
}
