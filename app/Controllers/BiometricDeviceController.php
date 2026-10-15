<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Integrations\ZKTeco\Services\BiometricSyncService;
use App\Models\AuditLog;
use App\Models\BiometricDevice;
use App\Models\Branch;

class BiometricDeviceController extends Controller
{
    private BiometricDevice $deviceModel;
    private BiometricSyncService $syncService;

    public function __construct()
    {
        $this->deviceModel = new BiometricDevice();
        $this->syncService = new BiometricSyncService();
    }

    public function index(Request $request): Response
    {
        $this->authorize('devices.view');

        $devices = $this->deviceModel->allWithStats();
        $branches = (new Branch())->all();
        $recentLogs = Database::fetchAll(
            "SELECT l.*, d.name as device_name 
             FROM `biometric_sync_logs` l 
             JOIN `biometric_devices` d ON d.id = l.device_id 
             ORDER BY l.id DESC 
             LIMIT 15"
        );

        return $this->view('devices/index', [
            'title' => 'إدارة أجهزة البصمة ZKTeco',
            'devices' => $devices,
            'branches' => $branches,
            'recentLogs' => $recentLogs,
        ]);
    }

    public function store(Request $request): Response
    {
        $this->authorize('devices.manage');

        $data = $this->validate($request, [
            'name' => 'required',
            'ip_address' => 'required',
            'port' => 'required|integer',
            'protocol' => 'required|in:direct_tcp,adms_push,mock_adapter',
        ]);

        $id = $this->deviceModel->create([
            'name' => trim($data['name']),
            'branch_id' => $request->post('branch_id') ?: null,
            'ip_address' => trim($data['ip_address']),
            'port' => (int)$data['port'],
            'protocol' => $data['protocol'],
            'comm_key' => $request->post('comm_key', '0'),
            'serial_number' => $request->post('serial_number'),
            'model_name' => $request->post('model_name'),
            'location_description' => $request->post('location_description'),
            'is_active' => 1,
            'last_sync_status' => 'idle',
        ]);

        AuditLog::log('create_device', 'BiometricDevice', $id, null, $data);
        $this->flash('success', "تم إضافة جهاز البصمة {$data['name']} بنجاح.");

        return $this->redirect(url('devices'));
    }

    public function testConnection(Request $request, string|int $id): Response
    {
        $this->authorize('devices.view');

        $device = $this->deviceModel->findOrFail((int)$id);
        $adapter = $this->syncService->getAdapterForDevice($device);

        $success = false;
        $info = [];
        $error = null;

        try {
            $success = $adapter->testConnection();
            if ($success) {
                try {
                    $info = $adapter->getDeviceInfo();
                } catch (\Throwable) {
                    $info = ['status' => 'connected'];
                }
            }
        } catch (\Throwable $e) {
            $error = $e->getMessage();
        }

        if ($request->isAjax()) {
            return $this->json([
                'success' => $success,
                'device_id' => $id,
                'info' => $info,
                'error' => $error ?: ($success ? null : 'فشل الاتصال بالجهاز.'),
            ]);
        }

        if ($success) {
            $this->flash('success', "تم الاتصال بجهاز {$device['name']} بنجاح.");
        } else {
            $this->flash('error', "فشل الاتصال بجهاز {$device['name']}: " . ($error ?? 'الجهاز غير متاح على الشبكة.'));
        }

        return $this->redirect(url('devices'));
    }

    public function sync(Request $request, string|int $id): Response
    {
        $this->authorize('devices.sync');

        $device = $this->deviceModel->findOrFail((int)$id);
        $result = $this->syncService->syncDevice($device, 'manual');

        if ($request->isAjax()) {
            return $this->json($result);
        }

        if ($result['success']) {
            $this->flash('success', "تمت مزامنة جهاز {$device['name']} بنجاح. السجلات المقروءة: {$result['records_fetched']}، الجديدة: {$result['records_inserted']}.");
        } else {
            $this->flash('error', "فشلت مزامنة جهاز {$device['name']}: {$result['error']}");
        }

        return $this->redirect(url('devices'));
    }

    /**
     * ZKTeco ADMS / PUSH endpoint handler.
     * Receives POST/GET from real ZKTeco iClock/SilkID terminals.
     */
    public function handleAdmsPush(Request $request): Response
    {
        $sn = $request->get('SN');
        $table = $request->get('table', 'ATTLOG');
        $rawBody = file_get_contents('php://input');

        if (empty($sn)) {
            return new Response('ERROR: Missing Serial Number', 400);
        }

        // Find device by SN
        $device = $this->deviceModel->firstWhere('serial_number', $sn);
        if (!$device) {
            // Auto-register device if unknown ADMS terminal connects
            $deviceId = $this->deviceModel->create([
                'name' => "ZKTeco Terminal ({$sn})",
                'ip_address' => $request->ip(),
                'port' => 4370,
                'protocol' => 'adms_push',
                'serial_number' => $sn,
                'model_name' => 'ADMS iClock Push Device',
                'is_active' => 1,
                'last_sync_status' => 'idle',
            ]);
            $device = $this->deviceModel->find($deviceId);
        }

        // If GET request, standard handshake/init from device
        if ($request->getMethod() === 'GET') {
            // Reply with standard ZK config string
            $configReply = "GET OPTION FROM: {$sn}\n" .
                "Stamp=9999\n" .
                "OpStamp=9999\n" .
                "PhotoStamp=9999\n" .
                "ErrorDelay=60\n" .
                "Delay=30\n" .
                "TransTimes=00:00;14:05\n" .
                "TransInterval=1\n" .
                "TransFlag=1111111111\n" .
                "TimeZone=3\n" .
                "Realtime=1\n" .
                "Encrypt=0\n";

            return new Response($configReply, 200, ['Content-Type' => 'text/plain']);
        }

        // If POST request, process attendance logs
        if (!empty($rawBody)) {
            $result = $this->syncService->syncDevice($device, 'adms_push', $rawBody);
            $inserted = $result['records_inserted'] ?? 0;
            return new Response("OK: {$inserted}", 200, ['Content-Type' => 'text/plain']);
        }

        return new Response("OK", 200, ['Content-Type' => 'text/plain']);
    }

    public function handleAdmsGetRequest(Request $request): Response
    {
        // Device polls for pending commands. If none, return OK.
        return new Response("OK", 200, ['Content-Type' => 'text/plain']);
    }

    public function handleAdmsDeviceCmd(Request $request): Response
    {
        return new Response("OK", 200, ['Content-Type' => 'text/plain']);
    }
}
