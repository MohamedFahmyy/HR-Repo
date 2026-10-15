<?php
/**
 * CLI Script: Sync Biometric Attendance
 * 
 * Usage:
 *   php bin/sync-attendance.php [--device-id=1] [--force]
 * 
 * Scheduled via cron / Windows Task Scheduler every 5-15 minutes.
 * Implements file-based & database process locking to prevent overlapping sync runs.
 */

declare(strict_types=1);

require_once __DIR__ . '/../bootstrap/app.php';

use App\Core\Application;
use App\Core\Database;
use App\Models\BiometricDevice;
use App\Integrations\ZKTeco\Services\BiometricSyncService;

// Prevent HTTP execution
if (php_sapi_name() !== 'cli') {
    die("Error: This script can only be executed via the CLI.\n");
}

$lockFile = sys_get_temp_dir() . '/enterprise_zkteco_sync.lock';
$lockFp = fopen($lockFile, 'c+');

if (!flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[!] Another synchronization process is already running. Exiting.\n";
    exit(0);
}

echo "====================================================\n";
echo "   Enterprise HRIS - ZKTeco Attendance Sync CLI   \n";
echo "   Time: " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n";

// Parse CLI options
$options = getopt('', ['device-id::', 'force::']);
$targetDeviceId = isset($options['device-id']) ? (int)$options['device-id'] : null;

$db = Database::getInstance();
$syncService = new BiometricSyncService();
$deviceModel = new BiometricDevice();

if ($targetDeviceId) {
    $devices = [$deviceModel->find($targetDeviceId)];
    if (empty($devices[0])) {
        echo "[-] Error: Device with ID {$targetDeviceId} not found.\n";
        flock($lockFp, LOCK_UN);
        fclose($lockFp);
        exit(1);
    }
} else {
    $devices = $deviceModel->where(['is_active' => 1]);
}

echo "[*] Found " . count($devices) . " active biometric device(s) to synchronize.\n\n";

$totalFetched = 0;
$totalInserted = 0;

foreach ($devices as $device) {
    echo "--> Syncing Device [ID: {$device['id']} | Name: '{$device['name']}' | IP: {$device['ip_address']}:{$device['port']}]...\n";
    try {
        $result = $syncService->syncDevice((int)$device['id'], 'cli_cron');
        echo "    Status: " . strtoupper($result['status']) . "\n";
        echo "    Records Fetched:  {$result['records_fetched']}\n";
        echo "    New Punches:      {$result['records_inserted']}\n";
        echo "    Message:          {$result['message']}\n\n";

        $totalFetched += $result['records_fetched'];
        $totalInserted += $result['records_inserted'];
    } catch (\Throwable $e) {
        echo "    [-] FAILED: " . $e->getMessage() . "\n\n";
    }
}

echo "----------------------------------------------------\n";
echo "[+] Sync completed successfully.\n";
echo "    Total Punches Fetched:  {$totalFetched}\n";
echo "    Total New Punches:      {$totalInserted}\n";
echo "====================================================\n";

flock($lockFp, LOCK_UN);
fclose($lockFp);
@unlink($lockFile);
exit(0);
