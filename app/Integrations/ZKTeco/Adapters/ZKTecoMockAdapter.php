<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\Adapters;

use App\Integrations\ZKTeco\Contracts\BiometricDeviceAdapterInterface;
use App\Integrations\ZKTeco\DTOs\RawBiometricPunchDTO;
use DateTimeImmutable;
use DateTimeInterface;

class ZKTecoMockAdapter implements BiometricDeviceAdapterInterface
{
    private int $deviceId;
    private array $simulatedPunches = [];
    private bool $simulateFailure = false;

    public function __construct(int $deviceId, array $simulatedPunches = [], bool $simulateFailure = false)
    {
        $this->deviceId = $deviceId;
        $this->simulatedPunches = $simulatedPunches;
        $this->simulateFailure = $simulateFailure;
    }

    public function testConnection(): bool
    {
        return !$this->simulateFailure;
    }

    public function getDeviceInfo(): array
    {
        if ($this->simulateFailure) {
            throw new \RuntimeException("Simulated hardware connection timeout.");
        }

        return [
            'device_id' => $this->deviceId,
            'name' => 'Mock Hardware Simulator',
            'firmware' => 'Ver 9.9.9 Mock Sim',
            'serial_number' => 'SIMULATED-ZK-001',
            'protocol' => 'mock_adapter',
            'user_count' => 500,
            'log_count' => 10000,
            'status' => 'online',
        ];
    }

    public function fetchAttendanceEvents(?DateTimeInterface $since = null): array
    {
        if ($this->simulateFailure) {
            throw new \RuntimeException("Simulated I/O failure while reading attendance logs.");
        }

        if (!empty($this->simulatedPunches)) {
            return $this->simulatedPunches;
        }

        // Generate sample punches for testing
        $now = new DateTimeImmutable();
        $morning = $now->setTime(7, 58, 20);
        $evening = $now->setTime(17, 4, 10);

        return [
            new RawBiometricPunchDTO($this->deviceId, '1001', $morning, 0, 1),
            new RawBiometricPunchDTO($this->deviceId, '1001', $evening, 1, 1),
            new RawBiometricPunchDTO($this->deviceId, '1002', $morning->modify('+10 minutes'), 0, 1),
            new RawBiometricPunchDTO($this->deviceId, '1002', $evening->modify('+5 minutes'), 1, 1),
            new RawBiometricPunchDTO($this->deviceId, '1003', $morning->modify('+35 minutes'), 0, 1), // Late 35m
            new RawBiometricPunchDTO($this->deviceId, '1003', $evening, 1, 1),
            new RawBiometricPunchDTO($this->deviceId, '1004', $morning, 0, 1),
            new RawBiometricPunchDTO($this->deviceId, '1004', $evening->modify('+2 hours'), 1, 1), // OT 2h
        ];
    }

    public function getLastSyncStatus(): array
    {
        return [
            'connected' => !$this->simulateFailure,
            'protocol' => 'mock_adapter',
            'last_sync' => date('Y-m-d H:i:s'),
        ];
    }
}
