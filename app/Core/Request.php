<?php

declare(strict_types=1);

namespace App\Core;

class Request
{
    private string $method;
    private string $uri;
    private array $getParams;
    private array $postParams;
    private array $serverParams;
    private array $files;
    private array $headers;
    private ?array $jsonPayload = null;

    public function __construct()
    {
        $this->serverParams = $_SERVER;
        $this->getParams = $_GET;
        $this->postParams = $_POST;
        $this->files = $_FILES;
        
        $method = $this->serverParams['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'POST' && isset($this->postParams['_method'])) {
            $method = strtoupper($this->postParams['_method']);
        }
        $this->method = $method;

        $this->parseHeaders();
        $this->parseJson();
        $this->parseUri();
    }

    private function parseHeaders(): void
    {
        $this->headers = [];
        foreach ($this->serverParams as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $header = strtolower(str_replace('_', '-', substr($key, 5)));
                $this->headers[$header] = $value;
            } elseif (in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'])) {
                $header = strtolower(str_replace('_', '-', $key));
                $this->headers[$header] = $value;
            }
        }
    }

    private function parseJson(): void
    {
        $contentType = $this->header('content-type', '');
        if (str_contains($contentType, 'application/json')) {
            $raw = file_get_contents('php://input');
            if (!empty($raw)) {
                $decoded = json_decode($raw, true);
                if (is_array($decoded)) {
                    $this->jsonPayload = $decoded;
                }
            }
        }
    }

    private function parseUri(): void
    {
        $uri = $this->serverParams['REQUEST_URI'] ?? '/';
        if (str_contains($uri, '?')) {
            $uri = explode('?', $uri, 2)[0];
        }

        // Handle script subdirectory if hosted under /test-folder/public or similar
        $scriptName = $this->serverParams['SCRIPT_NAME'] ?? '';
        $scriptDir = dirname($scriptName);
        
        if ($scriptDir !== '/' && $scriptDir !== '\\' && str_starts_with($uri, $scriptDir)) {
            $uri = substr($uri, strlen($scriptDir));
        }

        $this->uri = '/' . trim($uri, '/');
        if ($this->uri === '//') {
            $this->uri = '/';
        }
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->getParams[$key] ?? $default;
    }

    public function post(string $key, mixed $default = null): mixed
    {
        return $this->postParams[$key] ?? $default;
    }

    public function input(string $key, mixed $default = null): mixed
    {
        if ($this->jsonPayload !== null && array_key_exists($key, $this->jsonPayload)) {
            return $this->jsonPayload[$key];
        }
        if (array_key_exists($key, $this->postParams)) {
            return $this->postParams[$key];
        }
        if (array_key_exists($key, $this->getParams)) {
            return $this->getParams[$key];
        }
        return $default;
    }

    public function all(): array
    {
        return array_merge(
            $this->getParams,
            $this->postParams,
            $this->jsonPayload ?? []
        );
    }

    public function has(string $key): bool
    {
        return $this->input($key) !== null;
    }

    public function file(string $key): ?array
    {
        return $this->files[$key] ?? null;
    }

    public function header(string $key, mixed $default = null): mixed
    {
        $key = strtolower($key);
        return $this->headers[$key] ?? $default;
    }

    public function isAjax(): bool
    {
        return strtolower($this->header('x-requested-with', '')) === 'xmlhttprequest' || $this->isJson();
    }

    public function isJson(): bool
    {
        return str_contains($this->header('accept', ''), 'application/json') ||
               str_contains($this->header('content-type', ''), 'application/json');
    }

    public function ip(): string
    {
        return $this->serverParams['HTTP_X_FORWARDED_FOR'] ??
               $this->serverParams['REMOTE_ADDR'] ??
               '127.0.0.1';
    }

    public function userAgent(): string
    {
        return $this->serverParams['HTTP_USER_AGENT'] ?? 'Unknown';
    }
}
