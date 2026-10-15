<?php
/**
 * End-to-End System Testing Script
 * Exercises all web routes, authentication, ZKTeco endpoints, and reporting via HTTP.
 */

declare(strict_types=1);

$baseUrl = 'http://127.0.0.1/test-folder/public';
$cookieFile = sys_get_temp_dir() . '/hris_e2e_cookie.txt';
if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

function httpReq(string $method, string $url, ?array $postData = null, ?string $rawBody = null, bool $useCookies = true, array $extraHeaders = []): array
{
    global $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
    } else {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    }
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    if ($useCookies) {
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
        curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    }

    $headers = $extraHeaders;

    if ($postData !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
    } elseif ($rawBody !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, $rawBody);
        $headers[] = 'Content-Type: text/plain';
    }

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $finalUrl = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
    $err = curl_error($ch);
    curl_close($ch);

    return [
        'status' => $status,
        'url' => $finalUrl,
        'body' => $response,
        'error' => $err
    ];
}

echo "====================================================\n";
echo "   Enterprise HR & Payroll - E2E System Test Suite  \n";
echo "   Target: {$baseUrl}\n";
echo "   Time:   " . date('Y-m-d H:i:s') . "\n";
echo "====================================================\n\n";

$passCount = 0;
$failCount = 0;

function assertTest(string $name, bool $condition, string $detail = ''): void
{
    global $passCount, $failCount;
    if ($condition) {
        $passCount++;
        echo "  [PASS] {$name}\n";
    } else {
        $failCount++;
        echo "  [FAIL] {$name} (" . $detail . ")\n";
    }
}

// 1. Health check
echo "[1] Testing Core System & Health Endpoint...\n";
$res = httpReq('GET', "{$baseUrl}/health");
assertTest("GET /health returns HTTP 200", $res['status'] === 200, "Got status {$res['status']}");
$healthJson = json_decode($res['body'], true);
assertTest("GET /health response JSON contains status 'healthy'", ($healthJson['status'] ?? '') === 'healthy', $res['body']);

// 2. Login Page & CSRF token retrieval
echo "\n[2] Testing Authentication & Session Security...\n";
$res = httpReq('GET', "{$baseUrl}/login");
assertTest("GET /login returns HTTP 200", $res['status'] === 200, "Got status {$res['status']}");
preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $res['body'], $csrfMatches);
$csrfToken = $csrfMatches[1] ?? '';
assertTest("Login page renders CSRF token", !empty($csrfToken) && strlen($csrfToken) === 64, "Token: {$csrfToken}");

// 3. Admin Authentication POST
$loginRes = httpReq('POST', "{$baseUrl}/login", [
    '_csrf_token' => $csrfToken,
    'login' => 'admin@enterprise.local',
    'password' => 'Admin@123456'
]);
assertTest("POST /login authenticates and redirects to dashboard", str_contains($loginRes['url'], '/dashboard'), "Final URL: {$loginRes['url']}");

$dashRes = httpReq('GET', "{$baseUrl}/dashboard");
assertTest("GET /dashboard renders authenticated dashboard", $dashRes['status'] === 200 && (str_contains($dashRes['body'], 'stat-card') || str_contains($dashRes['body'], 'dashboard')));

// 4. Employee Management Module
echo "\n[3] Testing Employee Directory & Profile Modules...\n";
$res = httpReq('GET', "{$baseUrl}/employees");
assertTest("GET /employees returns HTTP 200", $res['status'] === 200);
assertTest("Employees table displays seeded employees", str_contains($res['body'], 'EMP-0001') && str_contains($res['body'], 'EMP-0002'));

$res = httpReq('GET', "{$baseUrl}/employees/1");
assertTest("GET /employees/1 displays detailed employee profile", $res['status'] === 200 && str_contains($res['body'], 'محمد المهندس'));

// 5. Attendance & Biometric Devices
echo "\n[4] Testing Biometric Devices & Attendance...\n";
$res = httpReq('GET', "{$baseUrl}/devices");
assertTest("GET /devices returns HTTP 200", $res['status'] === 200);
assertTest("Biometric devices table lists SilkID and Mock Simulator", str_contains($res['body'], 'SilkID') && str_contains($res['body'], 'Mock Simulator'));

// Test Mock device connection AJAX
$res = httpReq('GET', "{$baseUrl}/devices/3/test", null, null, true, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
assertTest("GET /devices/3/test returns HTTP 200", $res['status'] === 200);
$testJson = json_decode($res['body'], true);
assertTest("Mock terminal connection reports success", ($testJson['success'] ?? false) === true, "Response: {$res['body']}");

// Test Mock device sync AJAX
$res = httpReq('GET', "{$baseUrl}/devices/3/sync", null, null, true, ['X-Requested-With: XMLHttpRequest', 'Accept: application/json']);
assertTest("GET /devices/3/sync triggers biometric synchronization", $res['status'] === 200);
$syncJson = json_decode($res['body'], true);
assertTest("Mock sync completes successfully", ($syncJson['success'] ?? false) === true, "Response: {$res['body']}");

// Test Attendance Records view
$res = httpReq('GET', "{$baseUrl}/attendance");
assertTest("GET /attendance displays daily attendance ledger", $res['status'] === 200 && str_contains($res['body'], 'حضور'));

// 6. Shifts & Calendars
echo "\n[5] Testing Shifts & Calendars...\n";
$res = httpReq('GET', "{$baseUrl}/shifts");
assertTest("GET /shifts returns HTTP 200", $res['status'] === 200);
assertTest("Shifts list contains Standard and Overnight shifts", str_contains($res['body'], 'SH-STD') && str_contains($res['body'], 'SH-NIGHT'));

// 7. Leave Management
echo "\n[6] Testing Leave Ledger & Requests...\n";
$res = httpReq('GET', "{$baseUrl}/leaves");
assertTest("GET /leaves returns HTTP 200", $res['status'] === 200);
assertTest("Leaves view lists leave policies and requests", str_contains($res['body'], 'LV-ANN') || str_contains($res['body'], 'إجازة'));

// 8. Payroll Engine
echo "\n[7] Testing Payroll Engine...\n";
$res = httpReq('GET', "{$baseUrl}/payroll");
assertTest("GET /payroll returns HTTP 200", $res['status'] === 200);
assertTest("Payroll view lists current period and runs", str_contains($res['body'], 'مسير') || str_contains($res['body'], 'PAY-'));

$res = httpReq('GET', "{$baseUrl}/payroll/runs/1");
assertTest("GET /payroll/runs/1 displays itemized salary run", $res['status'] === 200 && (str_contains($res['body'], 'الراتب') || str_contains($res['body'], 'RUN-')));

// 9. Reports & Exports
echo "\n[8] Testing Reports & Analytics & CSV Export...\n";
$res = httpReq('GET', "{$baseUrl}/reports?type=attendance");
assertTest("GET /reports?type=attendance returns HTTP 200", $res['status'] === 200);
assertTest("Reports view renders data rows", str_contains($res['body'], 'EMP-0001') || str_contains($res['body'], 'تقرير'));

$exportRes = httpReq('GET', "{$baseUrl}/reports/export?type=attendance");
assertTest("GET /reports/export returns HTTP 200 CSV", $exportRes['status'] === 200 && (str_contains($exportRes['body'], 'employee_code') || str_contains($exportRes['body'], 'date')));

// 10. ZKTeco ADMS Push Endpoint
echo "\n[9] Testing ZKTeco ADMS / iClock HTTP Push Protocol...\n";
$samplePunchPayload = "1001\t" . date('Y-m-d H:i:s') . "\t0\t1\t0\n";
$res = httpReq('POST', "{$baseUrl}/api/zkteco/iclock/cdata?SN=CJ49202601001&table=ATTLOG", null, $samplePunchPayload, false);
assertTest("POST /api/zkteco/iclock/cdata accepts terminal push", $res['status'] === 200);
assertTest("ADMS push returns terminal OK acknowledgement", str_contains($res['body'], 'OK'), "Body: {$res['body']}");

// 11. Employee Self-Service (ESS)
echo "\n[10] Testing Employee Self-Service (ESS) Portal...\n";
if (file_exists($cookieFile)) {
    unlink($cookieFile); // Logout admin
}
$loginPage = httpReq('GET', "{$baseUrl}/login");
preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $loginPage['body'], $m);
$empCsrf = $m[1] ?? '';

$empLogin = httpReq('POST', "{$baseUrl}/login", [
    '_csrf_token' => $empCsrf,
    'login' => 'fahad.dev@enterprise.local',
    'password' => 'Emp@123456'
]);
assertTest("Employee logs in successfully", str_contains($empLogin['url'], '/dashboard') || str_contains($empLogin['url'], '/ess'));

$essRes = httpReq('GET', "{$baseUrl}/ess");
assertTest("GET /ess renders Employee Self-Service portal", $essRes['status'] === 200 && (str_contains($essRes['body'], 'فهد') || str_contains($essRes['body'], 'الذاتية') || str_contains($essRes['body'], 'الموظف')));
assertTest("ESS displays employee leave balances and punch history", str_contains($essRes['body'], 'متبقي') || str_contains($essRes['body'], 'رصيد') || str_contains($essRes['body'], 'إجازة'));

preg_match('/name="_csrf_token"\s+value="([^"]+)"/', $essRes['body'], $m);
$essCsrf = $m[1] ?? '';
$leaveSubmitRes = httpReq('POST', "{$baseUrl}/ess/leave", [
    '_csrf_token' => $essCsrf,
    'leave_type_id' => 1,
    'start_date' => date('Y-m-d', strtotime('+7 days')),
    'end_date' => date('Y-m-d', strtotime('+8 days')),
    'reason' => 'Automated E2E ESS leave test request',
]);
assertTest("ESS POST /ess/leave submits leave request and redirects", $leaveSubmitRes['status'] === 200 || $leaveSubmitRes['status'] === 302);


echo "\n====================================================\n";
echo "   Test Results: {$passCount} Passed, {$failCount} Failed\n";
echo "====================================================\n";

if (file_exists($cookieFile)) {
    unlink($cookieFile);
}

exit($failCount > 0 ? 1 : 0);
