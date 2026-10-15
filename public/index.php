<?php

declare(strict_types=1);

/**
 * Standalone Enterprise HR & Payroll System
 * Front Controller
 */

$app = require_once __DIR__ . '/../bootstrap/app.php';

// Register web routes
$router = $app->getRouter();
require_once __DIR__ . '/../routes/web.php';

// Dispatch request and send response
$app->run();
