<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\Adapters;

use App\Integrations\ZKTeco\Contracts\BiometricDeviceAdapterInterface;
use App\Integrations\ZKTeco\DTOs\RawBiometricPunchDTO;
use DateTimeImmutable;
use DateTimeInterface;

class ZKTecoAdmsPushAdapter implements BiometricDeviceAdapterInterface
{
    private int $deviceId;
    private string $serialNumber;
    private ?string $lastPayload;
    private array $lastStatus;

    public function __construct(int $deviceId, string $serialNumber = '', ?string $lastPayload = null)
    {
        $this->deviceId = $deviceId;
        $this->serialNumber = $serialNumber;
        $this->lastPayload = $lastPayload;
        $this->lastStatus = [
            'connected' => true,
            'protocol' => 'adms_push',
            'serial_number' => $serialNumber,
            'push_ready' => true,
        ];
    }

    public function testConnection(): bool
    {
        // ADMS devices push to server, so status is valid if device is registered and recently active
        return !empty($this->serialNumber);
    }

    public function getDeviceInfo(): array
    {
        return [
            'device_id' => $this->deviceId,
            'serial_number' => $this->serialNumber,
            'protocol' => 'adms_push',
            'push_endpoint' => '/api/zkteco/iclock/cdata',
            'status' => 'online',
        ];
    }

    public function fetchAttendanceEvents(?DateTimeInterface $since = null): array
    {
        if (empty($this->lastPayload)) {
            return [];
        }

        return $this->parseAdmsPayload($this->lastPayload, $since);
    }

    public function getLastSyncStatus(): array
    {
        return $this->lastStatus;
    }

    /**
     * Parses standard ZKTeco ADMS / iclock raw POST body.
     * Line format: PIN\tYYYY-MM-DD HH:MM:SS\tStatus\tVerifyType\tWorkCode
     */
    public function parseAdmsPayload(string $rawBody, ?DateTimeInterface $since = null): array
    {
        $lines = explode("\n", str_replace(["\r\n", "\r"], "\n", $rawBody));
        $punches = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) continue;

            $parts = preg_split('/[\t\s]+/', $line);
            if (count($parts) >= 2) {
                $pin = trim($parts[0]);
                $timeStr = trim($parts[1]);
                if (isset($parts[2]) && preg_match('/^\d{2}:\d{2}:\d{2}$/', $parts[2])) {
                    // Sometimes date and time are separated by space
                    $timeStr .= ' ' . trim($parts[2]);
                    $state = (int)($parts[3] ?? 0);
                    $verify = (int)($parts[4] ?? 1);
                    $workCode = (int)($parts[5] ?? 0);
                } else {
                    $state = (int)($parts[2] ?? 0);
                    $verify = (int)($parts[3] ?? 1);
                    $workCode = (int)($parts[4] ?? 0);
                }

                $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $timeStr);
                if (!$dt) {
                    $dt = new \DateTime($timeStr);
                }

                if ($dt && ($since === null || $dt >= $since)) {
                    $punches[] = new RawBiometricPunchDTO(
                        deviceId: $this->deviceId,
                        deviceUserId: $pin,
                        punchTime: DateTimeImmutable::createFromInterface($dt),
                        punchState: $state,
                        verifyType: $verify,
                        workCode: $workCode,
                        rawPayload: $line
                    );
                }
            }
        }

        return $punches;
    }
}
