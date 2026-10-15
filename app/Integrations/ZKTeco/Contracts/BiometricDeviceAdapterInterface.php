<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\Contracts;

use DateTimeInterface;

interface BiometricDeviceAdapterInterface
{
    /**
     * Test communication link to the biometric hardware or push endpoint.
     */
    public function testConnection(): bool;

    /**
     * Retrieve hardware details (serial number, firmware version, platform, user/log capacity).
     */
    public function getDeviceInfo(): array;

    /**
     * Fetch attendance punch events since a given timestamp.
     *
     * @param DateTimeInterface|null $since
     * @return \App\Integrations\ZKTeco\DTOs\RawBiometricPunchDTO[]
     */
    public function fetchAttendanceEvents(?DateTimeInterface $since = null): array;

    /**
     * Retrieve the last synchronization status and telemetry.
     */
    public function getLastSyncStatus(): array;
}
