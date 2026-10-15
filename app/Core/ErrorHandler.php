<?php

declare(strict_types=1);

namespace App\Core;

class ErrorHandler
{
    private static bool $debug = false;

    public static function register(bool $debug = false): void
    {
        self::$debug = $debug;

        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');

        set_error_handler([self::class, 'handleError']);
        set_exception_handler([self::class, 'handleException']);
        register_shutdown_function([self::class, 'handleShutdown']);
    }

    public static function handleError(int $level, string $message, string $file = '', int $line = 0): bool
    {
        if (!(error_reporting() & $level)) {
            return false;
        }

        throw new \ErrorException($message, 0, $level, $file, $line);
    }

    public static function handleException(\Throwable $e): void
    {
        self::logException($e);

        if (php_sapi_name() === 'cli') {
            fwrite(STDERR, "\n[ERROR] " . $e->getMessage() . "\n");
            fwrite(STDERR, "File: " . $e->getFile() . ":" . $e->getLine() . "\n");
            if (self::$debug) {
                fwrite(STDERR, $e->getTraceAsString() . "\n");
            }
            exit(1);
        }

        if (!headers_sent()) {
            http_response_code(500);
        }

        $isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

        if ($isJson) {
            $payload = [
                'success' => false,
                'error' => self::$debug ? $e->getMessage() : 'An internal server error occurred.'
            ];
            if (self::$debug) {
                $payload['trace'] = $e->getTraceAsString();
                $payload['file'] = $e->getFile();
                $payload['line'] = $e->getLine();
            }
            Response::json($payload, 500)->send();
            exit;
        }

        try {
            View::render('errors/500', [
                'exception' => $e,
                'debug' => self::$debug,
                'message' => self::$debug ? $e->getMessage() : 'حدث خطأ داخلي في الخادم. يرجى المحاولة مرة أخرى لاحقاً.'
            ], 'layouts/error')->setStatusCode(500)->send();
        } catch (\Throwable $renderError) {
            echo "<h1>500 Internal Server Error</h1>";
            if (self::$debug) {
                echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
                echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
            } else {
                echo "<p>An unexpected error occurred. Please contact the system administrator.</p>";
            }
        }
        exit;
    }

    public static function handleShutdown(): void
    {
        $error = error_get_last();
        if ($error !== null && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            self::handleException(new \ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
        }
    }

    public static function logException(\Throwable $e): void
    {
        $logDir = Application::getInstance()->getStoragePath('logs');
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }

        $logFile = $logDir . DIRECTORY_SEPARATOR . 'error-' . date('Y-m-d') . '.log';
        $entry = sprintf(
            "[%s] %s: %s in %s:%d\nStack trace:\n%s\n----------------------------------------\n",
            date('Y-m-d H:i:s'),
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );

        @file_put_contents($logFile, $entry, FILE_APPEND);
    }
}
