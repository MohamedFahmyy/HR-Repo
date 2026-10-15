<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">لوحة التحكم والمؤشرات التشغيلية</h3>
        <p class="text-muted small mb-0">نظرة عامة لحظية على حضور وانصراف الموظفين، أجهزة البصمة ومسيرات الرواتب</p>
    </div>
    <div class="d-flex gap-2">
        <form action="<?= url('attendance/calculate') ?>" method="POST" class="d-inline">
            <?= csrf_field() ?>
            <input type="hidden" name="start_date" value="<?= date('Y-m-d') ?>">
            <input type="hidden" name="end_date" value="<?= date('Y-m-d') ?>">
            <button type="submit" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                <i class="bi bi-arrow-repeat me-1"></i> احتساب حضور اليوم
            </button>
        </form>
        <a href="<?= url('employees/create') ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
            <i class="bi bi-person-plus-fill me-1"></i> إضافة موظف
        </a>
    </div>
</div>

<!-- Primary Stats Grid -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-people-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['active_employees']) ?></div>
                <div class="stat-label">الموظفون على رأس العمل (من <?= $stats['total_employees'] ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['present_today']) ?></div>
                <div class="stat-label">حضور اليوم في المنشأة</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-clock-history"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['late_today']) ?></div>
                <div class="stat-label">متأخرون اليوم عن موعد الوردية</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card">
            <div class="stat-icon danger">
                <i class="bi bi-x-circle-fill"></i>
            </div>
            <div>
                <div class="stat-value"><?= number_format($stats['absent_today']) ?></div>
                <div class="stat-label">غياب اليوم غير مبرر</div>
            </div>
        </div>
    </div>
</div>

<!-- Secondary Metrics Bar -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="content-card mb-0 p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small">في إجازة رسمية اليوم:</span>
                <div class="fw-bold fs-5 text-primary"><?= number_format($stats['on_leave_today']) ?> موظف</div>
            </div>
            <i class="bi bi-calendar2-range text-primary fs-3"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="content-card mb-0 p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small">طلبات قيد المراجعة:</span>
                <div class="fw-bold fs-5 text-warning"><?= number_format($stats['pending_leaves'] + $stats['pending_adjustments']) ?> طلب</div>
            </div>
            <i class="bi bi-bell-fill text-warning fs-3"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="content-card mb-0 p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small">حالة أجهزة ZKTeco:</span>
                <div class="fw-bold fs-5 text-success"><?= $stats['device_success'] ?> / <?= $stats['active_devices'] ?> متصلة</div>
            </div>
            <i class="bi bi-fingerprint text-success fs-3"></i>
        </div>
    </div>
    <div class="col-md-3">
        <div class="content-card mb-0 p-3 d-flex align-items-center justify-content-between">
            <div>
                <span class="text-muted small">آخر مسير رواتب:</span>
                <div class="fw-bold fs-5 text-dark"><?= $stats['latest_payroll'] ? number_format((float)$stats['latest_payroll']['total_net'], 2) . ' ' . config('payroll.currency_symbol') : 'لا يوجد' ?></div>
            </div>
            <i class="bi bi-wallet2 text-success fs-3"></i>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="row g-3 mb-4">
    <div class="col-lg-8">
        <div class="content-card h-100 mb-0">
            <div class="card-header">
                <h5><i class="bi bi-graph-up text-primary me-2"></i> اتجاهات الحضور والانضباط (آخر 7 أيام)</h5>
            </div>
            <div class="card-body">
                <canvas id="attendanceTrendChart" height="110"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="content-card h-100 mb-0">
            <div class="card-header">
                <h5><i class="bi bi-pie-chart text-primary me-2"></i> توزيع الموظفين بالأقسام</h5>
            </div>
            <div class="card-body d-flex align-items-center justify-content-center">
                <div style="width: 100%; max-width: 280px;">
                    <canvas id="deptDistributionChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Live Biometric Raw Punches Feed -->
<div class="content-card mb-0">
    <div class="card-header">
        <h5><i class="bi bi-activity text-success me-2"></i> سجل البصمات اللحظي من أجهزة ZKTeco (Live Raw Punches)</h5>
        <a href="<?= url('devices') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-hdd-network me-1"></i> إدارة الأجهزة والمزامنة
        </a>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>توقيت البصمة</th>
                    <th>جهاز البصمة</th>
                    <th>رقم المستخدم (PIN)</th>
                    <th>الموظف المرتبط</th>
                    <th>نوع الحركة</th>
                    <th>طريقة التحقق</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentPunches)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">لا توجد حركات بصمة مسجلة حالياً.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentPunches as $p): ?>
                        <tr>
                            <td class="fw-semibold text-dark">
                                <i class="bi bi-clock me-1 text-muted"></i>
                                <?= date('Y-m-d H:i:s', strtotime($p['punch_time'])) ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($p['device_name'] ?? 'جهاز خارجي') ?></span></td>
                            <td><code><?= e($p['device_user_id']) ?></code></td>
                            <td>
                                <?php if (!empty($p['employee_code'])): ?>
                                    <span class="fw-semibold"><?= e($p['employee_name']) ?></span>
                                    <small class="text-muted d-block"><?= e($p['employee_code']) ?></small>
                                <?php else: ?>
                                    <span class="text-warning small"><i class="bi bi-exclamation-circle me-1"></i> غير مرتبط بموظف</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                    $states = [
                                        0 => ['حضور (Check-In)', 'success'],
                                        1 => ['انصراف (Check-Out)', 'danger'],
                                        2 => ['خروج استراحة (Break-Out)', 'warning'],
                                        3 => ['عودة استراحة (Break-In)', 'info'],
                                        4 => ['بدء إضافي (OT-In)', 'primary'],
                                        5 => ['انتهاء إضافي (OT-Out)', 'secondary'],
                                    ];
                                    $stateInfo = $states[(int)$p['punch_state']] ?? ['غير محدد', 'secondary'];
                                ?>
                                <span class="badge bg-<?= $stateInfo[1] ?>-subtle text-<?= $stateInfo[1] ?> rounded-pill px-2 py-1">
                                    <?= $stateInfo[0] ?>
                                </span>
                            </td>
                            <td>
                                <?php
                                    $verifyTypes = [1 => 'بصمة إصبع', 2 => 'كلمة مرور', 3 => 'بطاقة RFID', 4 => 'بصمة وجه'];
                                    echo $verifyTypes[(int)$p['verify_type']] ?? 'بيومترك';
                                ?>
                            </td>
                            <td>
                                <?php if ((int)$p['processed'] === 1): ?>
                                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check2"></i> تمت المعالجة</span>
                                <?php else: ?>
                                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-hourglass"></i> بانتظار الاحتساب</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Attendance Trend Chart
    const trendCtx = document.getElementById('attendanceTrendChart');
    if (trendCtx) {
        new Chart(trendCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($charts['trend_dates']) ?>,
                datasets: [
                    {
                        label: 'حضور منتظم',
                        data: <?= json_encode($charts['trend_present']) ?>,
                        backgroundColor: '#10b981',
                        borderRadius: 6
                    },
                    {
                        label: 'متأخرون',
                        data: <?= json_encode($charts['trend_late']) ?>,
                        backgroundColor: '#f59e0b',
                        borderRadius: 6
                    },
                    {
                        label: 'غياب',
                        data: <?= json_encode($charts['trend_absent']) ?>,
                        backgroundColor: '#ef4444',
                        borderRadius: 6
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'top', rtl: true }
                },
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } }
                }
            }
        });
    }

    // 2. Department Distribution Chart
    const deptCtx = document.getElementById('deptDistributionChart');
    if (deptCtx) {
        new Chart(deptCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($charts['dept_labels']) ?>,
                datasets: [{
                    data: <?= json_encode($charts['dept_values']) ?>,
                    backgroundColor: ['#2563eb', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4'],
                    borderWidth: 2,
                    borderColor: '#ffffff'
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: { position: 'bottom', rtl: true }
                }
            }
        });
    }
});
</script>
