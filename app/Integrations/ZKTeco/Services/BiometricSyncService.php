<?php

declare(strict_types=1);

namespace App\Integrations\ZKTeco\Services;

use App\Core\Database;
use App\Integrations\ZKTeco\Adapters\ZKTecoAdmsPushAdapter;
use App\Integrations\ZKTeco\Adapters\ZKTecoDirectTcpAdapter;
use App\Integrations\ZKTeco\Adapters\ZKTecoMockAdapter;
use App\Integrations\ZKTeco\Contracts\BiometricDeviceAdapterInterface;
use App\Integrations\ZKTeco\DTOs\RawBiometricPunchDTO;
use App\Models\BiometricDevice;
use App\Models\BiometricRawEvent;
use App\Models\BiometricSyncLog;
use App\Services\AttendanceCalculationService;
use DateTimeImmutable;

class BiometricSyncService
{
    private BiometricDevice $deviceModel;
    private BiometricRawEvent $rawEventModel;
    private BiometricSyncLog $syncLogModel;
    private AttendanceCalculationService $calcService;

    public function __construct(
        ?BiometricDevice $deviceModel = null,
        ?BiometricRawEvent $rawEventModel = null,
        ?BiometricSyncLog $syncLogModel = null,
        ?AttendanceCalculationService $calcService = null
    ) {
        $this->deviceModel = $deviceModel ?? new BiometricDevice();
        $this->rawEventModel = $rawEventModel ?? new BiometricRawEvent();
        $this->syncLogModel = $syncLogModel ?? new BiometricSyncLog();
        $this->calcService = $calcService ?? new AttendanceCalculationService();
    }

    public function getAdapterForDevice(array $device, ?string $rawPayload = null): BiometricDeviceAdapterInterface
    {
        $protocol = $device['protocol'] ?? 'direct_tcp';
        $deviceId = (int)$device['id'];

        return match ($protocol) {
            'adms_push' => new ZKTecoAdmsPushAdapter($deviceId, $device['serial_number'] ?? '', $rawPayload),
            'mock_adapter' => new ZKTecoMockAdapter($deviceId),
            default => new ZKTecoDirectTcpAdapter(
                $deviceId,
                $device['ip_address'],
                (int)($device['port'] ?? 4370),
                $device['comm_key'] ?? '0'
            ),
        };
    }

    public function syncDevice(int|array $deviceOrId, string $syncType = 'manual', ?string $rawPayload = null): array
    {
        $startTime = microtime(true);
        $device = is_array($deviceOrId) ? $deviceOrId : $this->deviceModel->findOrFail($deviceOrId);
        $deviceId = (int)$device['id'];

        // Mark device syncing
        Database::execute(
            "UPDATE `biometric_devices` SET `last_sync_status` = 'syncing' WHERE `id` = :id",
            ['id' => $deviceId]
        );

        $adapter = $this->getAdapterForDevice($device, $rawPayload);
        $fetched = 0;
        $inserted = 0;
        $status = 'success';
        $errorMsg = null;
        $latestPunchTime = null;

        try {
            /** @var RawBiometricPunchDTO[] $punches */
            $punches = $adapter->fetchAttendanceEvents();
            $fetched = count($punches);

            foreach ($punches as $punch) {
                $rawArray = $punch->toArray();
                if ($this->rawEventModel->insertIdempotent($rawArray)) {
                    $inserted++;
                }

                $punchTimeStr = $rawArray['punch_time'];
                if ($latestPunchTime === null || $punchTimeStr > $latestPunchTime) {
                    $latestPunchTime = $punchTimeStr;
                }
            }

            // Trigger calculation for the dates involved
            if ($inserted > 0 || $fetched > 0) {
                $this->calcService->processUnprocessedRawEvents();
            }

            // Update device status
            Database::execute(
                "UPDATE `biometric_devices` 
                 SET `last_sync_status` = 'success', 
                     `last_sync_at` = NOW(), 
                     `last_punch_at` = COALESCE(:punch_at, `last_punch_at`),
                     `last_error_message` = NULL 
                 WHERE `id` = :id",
                ['id' => $deviceId, 'punch_at' => $latestPunchTime]
            );

        } catch (\Throwable $e) {
            $status = 'failed';
            $errorMsg = $e->getMessage();

            Database::execute(
                "UPDATE `biometric_devices` 
                 SET `last_sync_status` = 'failed', 
                     `last_error_message` = :err 
                 WHERE `id` = :id",
                ['id' => $deviceId, 'err' => $errorMsg]
            );
        }

        $duration = round(microtime(true) - $startTime, 2);

        // Record in sync logs
        $this->syncLogModel->create([
            'device_id' => $deviceId,
            'sync_type' => $syncType,
            'status' => $status,
            'records_fetched' => $fetched,
            'records_inserted' => $inserted,
            'error_message' => $errorMsg,
            'duration_seconds' => $duration,
        ]);

        return [
            'success' => $status === 'success',
            'status' => $status,
            'message' => $status === 'success' 
                ? "Synchronized {$inserted} new punches (fetched {$fetched})." 
                : ($errorMsg ?? 'Sync failed'),
            'device_id' => $deviceId,
            'device_name' => $device['name'],
            'records_fetched' => $fetched,
            'records_inserted' => $inserted,
            'duration' => $duration,
            'error' => $errorMsg,
        ];
    }

    public function syncAllActiveDevices(string $syncType = 'cli_cron'): array
    {
        $devices = $this->deviceModel->where('is_active', 1);
        $results = [];

        foreach ($devices as $device) {
            $results[] = $this->syncDevice($device, $syncType);
        }

        return $results;
    }
}
