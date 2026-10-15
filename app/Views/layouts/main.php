<!DOCTYPE html>
<html lang="<?= e($appConfig['locale'] ?? 'ar') ?>" dir="<?= ($appConfig['locale'] ?? 'ar') === 'ar' ? 'rtl' : 'ltr' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= e($title ?? 'نظام الموارد البشرية والرواتب') ?> | <?= e($appConfig['name']) ?></title>

    <?php if (($appConfig['locale'] ?? 'ar') === 'ar'): ?>
        <!-- Bootstrap 5 RTL -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.rtl.min.css">
    <?php else: ?>
        <!-- Bootstrap 5 LTR -->
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php endif; ?>

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Custom Enterprise CSS -->
    <link rel="stylesheet" href="<?= asset('css/app.css') ?>">
    <script>window.APP_URL = "<?= rtrim(config('app.url', ''), '/') ?>";</script>
</head>
<body>
    <div class="app-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="app-sidebar">
            <a href="<?= url('dashboard') ?>" class="sidebar-brand">
                <div class="brand-icon">
                    <i class="bi bi-buildings-fill"></i>
                </div>
                <div>
                    <div class="brand-text"><?= e(config('app.name')) ?></div>
                    <div class="brand-sub">Enterprise Edition 2026</div>
                </div>
            </a>

            <ul class="sidebar-menu">
                <li class="menu-header"><?= __('dashboard') ?></li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/dashboard') ? 'active' : '' ?>">
                    <a href="<?= url('dashboard') ?>" class="menu-link">
                        <i class="bi bi-grid-1x2-fill"></i>
                        <span><?= __('dashboard') ?></span>
                    </a>
                </li>

                <?php if (can('employees.view')): ?>
                <li class="menu-header"><?= __('employees') ?></li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/employees') ? 'active' : '' ?>">
                    <a href="<?= url('employees') ?>" class="menu-link">
                        <i class="bi bi-people-fill"></i>
                        <span><?= __('employees') ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('departments.manage')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/departments') ? 'active' : '' ?>">
                    <a href="<?= url('departments') ?>" class="menu-link">
                        <i class="bi bi-diagram-3-fill"></i>
                        <span><?= __('departments') ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('devices.view') || can('attendance.view')): ?>
                <li class="menu-header"><?= __('attendance') ?> & ZKTeco</li>
                <?php if (can('devices.view')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/devices') ? 'active' : '' ?>">
                    <a href="<?= url('devices') ?>" class="menu-link">
                        <i class="bi bi-fingerprint"></i>
                        <span><?= __('devices') ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('attendance.view')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/attendance') && !str_contains($_SERVER['REQUEST_URI'] ?? '', 'adjustments') ? 'active' : '' ?>">
                    <a href="<?= url('attendance') ?>" class="menu-link">
                        <i class="bi bi-calendar2-check-fill"></i>
                        <span><?= __('attendance') ?></span>
                    </a>
                </li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/attendance/adjustments') ? 'active' : '' ?>">
                    <a href="<?= url('attendance/adjustments') ?>" class="menu-link">
                        <i class="bi bi-clock-history"></i>
                        <span>طلبات التصحيح</span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('shifts.manage')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/shifts') ? 'active' : '' ?>">
                    <a href="<?= url('shifts') ?>" class="menu-link">
                        <i class="bi bi-alarm-fill"></i>
                        <span><?= __('shifts') ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <?php if (can('leaves.view') || can('overtime.view')): ?>
                <li class="menu-header"><?= __('leaves') ?> & الإضافي</li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/leaves') ? 'active' : '' ?>">
                    <a href="<?= url('leaves') ?>" class="menu-link">
                        <i class="bi bi-calendar-event-fill"></i>
                        <span><?= __('leaves') ?></span>
                    </a>
                </li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/overtime') ? 'active' : '' ?>">
                    <a href="<?= url('overtime') ?>" class="menu-link">
                        <i class="bi bi-hourglass-split"></i>
                        <span><?= __('overtime') ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('payroll.view')): ?>
                <li class="menu-header"><?= __('payroll') ?></li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/payroll') ? 'active' : '' ?>">
                    <a href="<?= url('payroll') ?>" class="menu-link">
                        <i class="bi bi-cash-stack"></i>
                        <span><?= __('payroll') ?></span>
                    </a>
                </li>
                <?php endif; ?>

                <?php if (can('recruitment.manage') || can('performance.manage')): ?>
                <li class="menu-header"><?= __('recruitment') ?> والأداء</li>
                <?php if (can('recruitment.manage')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/recruitment') ? 'active' : '' ?>">
                    <a href="<?= url('recruitment') ?>" class="menu-link">
                        <i class="bi bi-person-plus-fill"></i>
                        <span><?= __('recruitment') ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can('performance.manage')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/performance') ? 'active' : '' ?>">
                    <a href="<?= url('performance') ?>" class="menu-link">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span><?= __('performance') ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>

                <li class="menu-header"><?= __('ess') ?></li>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/ess') ? 'active' : '' ?>">
                    <a href="<?= url('ess') ?>" class="menu-link">
                        <i class="bi bi-person-badge-fill"></i>
                        <span><?= __('ess') ?></span>
                    </a>
                </li>

                <?php if (can('reports.view') || can('settings.manage')): ?>
                <li class="menu-header"><?= __('reports') ?> & النظام</li>
                <?php if (can('reports.view')): ?>
                <li class="menu-header-item menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/reports') ? 'active' : '' ?>">
                    <a href="<?= url('reports') ?>" class="menu-link">
                        <i class="bi bi-file-earmark-bar-graph-fill"></i>
                        <span><?= __('reports') ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php if (can('settings.manage')): ?>
                <li class="menu-item <?= str_contains($_SERVER['REQUEST_URI'] ?? '', '/settings') ? 'active' : '' ?>">
                    <a href="<?= url('settings') ?>" class="menu-link">
                        <i class="bi bi-gear-fill"></i>
                        <span><?= __('settings') ?></span>
                    </a>
                </li>
                <?php endif; ?>
                <?php endif; ?>
            </ul>
        </aside>

        <!-- Main Wrapper -->
        <div class="app-main">
            <!-- Navbar -->
            <header class="app-navbar">
                <div class="d-flex align-items-center gap-3">
                    <button class="btn btn-light d-lg-none" id="sidebarToggle">
                        <i class="bi bi-list fs-5"></i>
                    </button>
                    <div class="d-none d-sm-block">
                        <span class="badge bg-primary-subtle text-primary fw-semibold px-3 py-2 rounded-pill">
                            <i class="bi bi-shield-check me-1"></i> اتصال مشفر آمن
                        </span>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3">
                    <!-- Language Switcher -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle rounded-pill px-3" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-translate me-1"></i>
                            <?= ($appConfig['locale'] ?? 'ar') === 'ar' ? 'العربية' : 'English' ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                            <li><a class="dropdown-item" href="<?= url('lang?lang=ar') ?>">العربية (RTL)</a></li>
                            <li><a class="dropdown-item" href="<?= url('lang?lang=en') ?>">English (LTR)</a></li>
                        </ul>
                    </div>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn btn-light rounded-pill p-1 d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" style="width: 36px; height: 36px; font-weight: 600;">
                                <?= mb_substr($currentUser['full_name_ar'] ?? 'A', 0, 1) ?>
                            </div>
                            <span class="d-none d-md-inline fw-semibold text-dark pe-2"><?= e($currentUser['full_name_ar'] ?? 'المستخدم') ?></span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0 mt-2">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-bold"><?= e($currentUser['full_name_ar'] ?? '') ?></div>
                                <div class="text-muted small"><?= e($currentUser['email'] ?? '') ?></div>
                            </li>
                            <li><a class="dropdown-item py-2" href="<?= url('ess') ?>"><i class="bi bi-person me-2"></i><?= __('ess') ?></a></li>
                            <?php if (can('settings.manage')): ?>
                            <li><a class="dropdown-item py-2" href="<?= url('settings') ?>"><i class="bi bi-gear me-2"></i><?= __('settings') ?></a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="<?= url('logout') ?>" method="POST" class="m-0">
                                    <?= csrf_field() ?>
                                    <button type="submit" class="dropdown-item py-2 text-danger">
                                        <i class="bi bi-box-arrow-right me-2"></i><?= __('logout') ?>
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <!-- Page Content Body -->
            <main class="app-content">
                <!-- Flash Messages -->
                <?php if ($msg = flash('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill fs-5"></i>
                        <div><?= e($msg) ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($msg = flash('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                        <div><?= e($msg) ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($msg = flash('info')): ?>
                    <div class="alert alert-info alert-dismissible fade show d-flex align-items-center gap-2 border-0 shadow-sm" role="alert">
                        <i class="bi bi-info-circle-fill fs-5"></i>
                        <div><?= e($msg) ?></div>
                        <button type="button" class="btn-close ms-auto" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- View Content -->
                <?= $content ?>
            </main>
        </div>
    </div>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Custom Application JS -->
    <script src="<?= asset('js/app.js') ?>"></script>
</body>
</html>
