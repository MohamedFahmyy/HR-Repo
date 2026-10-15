<?php

declare(strict_types=1);

if (!function_exists('app')) {
    function app(): \App\Core\Application
    {
        return \App\Core\Application::getInstance();
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \App\Core\Application::getInstance()->getConfig($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = [], string $layout = 'layouts/main'): \App\Core\Response
    {
        return \App\Core\View::render($template, $data, $layout);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $statusCode = 302): \App\Core\Response
    {
        return \App\Core\Response::redirect($url, $statusCode);
    }
}

if (!function_exists('json_response')) {
    function json_response(mixed $data, int $status = 200): \App\Core\Response
    {
        return \App\Core\Response::json($data, $status);
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \App\Core\Csrf::getToken();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return '<input type="hidden" name="_csrf_token" value="' . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') . '">';
    }
}

if (!function_exists('e')) {
    function e(?string $value): string
    {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('auth')) {
    function auth(): ?\App\Core\Auth
    {
        return \App\Core\Auth::getInstance();
    }
}

if (!function_exists('user')) {
    function user(): ?array
    {
        return \App\Core\Auth::getInstance()->user();
    }
}

if (!function_exists('has_role')) {
    function has_role(string $role): bool
    {
        return \App\Core\Auth::getInstance()->hasRole($role);
    }
}

if (!function_exists('can')) {
    function can(string $permission): bool
    {
        return \App\Core\Auth::getInstance()->can($permission);
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $path = ltrim($path, '/');
        $scriptDir = isset($_SERVER['SCRIPT_NAME']) ? dirname($_SERVER['SCRIPT_NAME']) : '';
        if ($scriptDir && $scriptDir !== '/' && $scriptDir !== '\\') {
            $base = rtrim(str_replace('\\', '/', $scriptDir), '/');
        } else {
            $configUrl = config('app.url', '');
            $parsedPath = parse_url($configUrl, PHP_URL_PATH);
            $base = $parsedPath ? rtrim($parsedPath, '/') : '';
        }
        return $path === '' ? ($base ?: '/') : "{$base}/{$path}";
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        return url('assets/' . ltrim($path, '/'));
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string
    {
        if ($message !== null) {
            \App\Core\Session::flash($key, $message);
            return null;
        }
        return \App\Core\Session::getFlash($key);
    }
}

if (!function_exists('trans')) {
    function trans(string $key, array $replace = []): string
    {
        return \App\Helpers\Translation::get($key, $replace);
    }
}

if (!function_exists('__')) {
    function __(string $key, array $replace = []): string
    {
        return trans($key, $replace);
    }
}
