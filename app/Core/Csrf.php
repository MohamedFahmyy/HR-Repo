<?php

declare(strict_types=1);

namespace App\Core;

class Csrf
{
    private const SESSION_TOKEN_KEY = '_csrf_token';

    public static function getToken(): string
    {
        $token = Session::get(self::SESSION_TOKEN_KEY);
        if (!$token) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::SESSION_TOKEN_KEY, $token);
        }
        return $token;
    }

    public static function generate(): string
    {
        return self::getToken();
    }

    public static function validate(Request|string $requestOrToken): bool
    {
        if (!config('security.csrf.enabled', true)) {
            return true;
        }

        // Safe methods do not require CSRF
        if ($requestOrToken instanceof Request && in_array($requestOrToken->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return true;
        }

        $sessionToken = Session::get(self::SESSION_TOKEN_KEY);
        if (!$sessionToken) {
            return false;
        }

        if (is_string($requestOrToken)) {
            return !empty($requestOrToken) && hash_equals($sessionToken, $requestOrToken);
        }

        $formToken = $requestOrToken->post('_csrf_token') ?? $requestOrToken->input('_csrf_token');
        $headerToken = $requestOrToken->header('x-csrf-token');

        $submittedToken = $formToken ?: $headerToken;

        if (!$submittedToken || !is_string($submittedToken)) {
            return false;
        }

        return hash_equals($sessionToken, $submittedToken);
    }

    public static function regenerate(): string
    {
        $token = bin2hex(random_bytes(32));
        Session::set(self::SESSION_TOKEN_KEY, $token);
        return $token;
    }
}
