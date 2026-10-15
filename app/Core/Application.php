<?php

declare(strict_types=1);

namespace App\Core;

class Application
{
    private static ?Application $instance = null;
    private array $configs = [];
    private string $basePath;
    private Router $router;
    private Request $request;
    private Response $response;

    public function __construct(string $basePath)
    {
        self::$instance = $this;
        $this->basePath = rtrim($basePath, '/\\');
        
        $this->loadEnvironment();
        $this->loadConfigurations();
        
        // Set timezone
        date_default_timezone_set($this->getConfig('app.timezone', 'Asia/Riyadh'));
        
        // Initialize Error Handler
        ErrorHandler::register($this->getConfig('app.debug', false));
        
        // Initialize Session
        Session::start();
        
        $this->request = new Request();
        $this->response = new Response();
        $this->router = new Router($this->request, $this->response);
    }

    public static function getInstance(): Application
    {
        if (self::$instance === null) {
            throw new \RuntimeException('Application has not been initialized.');
        }
        return self::$instance;
    }

    public function getBasePath(): string
    {
        return $this->basePath;
    }

    public function getStoragePath(string $path = ''): string
    {
        return $this->basePath . DIRECTORY_SEPARATOR . 'storage' . ($path ? DIRECTORY_SEPARATOR . ltrim($path, '/\\') : '');
    }

    public function getRouter(): Router
    {
        return $this->router;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }

    public function getResponse(): Response
    {
        return $this->response;
    }

    public function getConfig(string $key, mixed $default = null): mixed
    {
        $parts = explode('.', $key);
        $file = array_shift($parts);

        if (!isset($this->configs[$file])) {
            $configPath = $this->basePath . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . "{$file}.php";
            if (file_exists($configPath)) {
                $this->configs[$file] = require $configPath;
            } else {
                return $default;
            }
        }

        $current = $this->configs[$file];
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }

        return $current;
    }

    private function loadEnvironment(): void
    {
        $envPath = $this->basePath . DIRECTORY_SEPARATOR . '.env';
        if (!file_exists($envPath)) {
            $envPath = $this->basePath . DIRECTORY_SEPARATOR . '.env.example';
        }

        if (file_exists($envPath)) {
            $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === '' || str_starts_with($line, '#')) {
                    continue;
                }
                if (str_contains($line, '=')) {
                    [$name, $value] = explode('=', $line, 2);
                    $name = trim($name);
                    $value = trim($value);
                    $value = trim($value, '"\'');
                    $_ENV[$name] = $value;
                    putenv("{$name}={$value}");
                }
            }
        }
    }

    private function loadConfigurations(): void
    {
        $configDir = $this->basePath . DIRECTORY_SEPARATOR . 'config';
        if (is_dir($configDir)) {
            foreach (glob($configDir . DIRECTORY_SEPARATOR . '*.php') as $file) {
                $key = basename($file, '.php');
                $this->configs[$key] = require $file;
            }
        }
    }

    public function run(): void
    {
        $response = $this->router->dispatch();
        $response->send();
    }
}
