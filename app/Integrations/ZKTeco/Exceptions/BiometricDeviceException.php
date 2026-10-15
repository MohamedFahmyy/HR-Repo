<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\Exceptions;

use RuntimeException;

class BiometricDeviceException extends RuntimeException
{
    private ?int $deviceId;
    private array $context;

    public function __construct(string $message, int $code = 0, ?int $deviceId = null, array $context = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->deviceId = $deviceId;
        $this->context = $context;
    }

    public function getDeviceId(): ?int
    {
        return $this->deviceId;
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
