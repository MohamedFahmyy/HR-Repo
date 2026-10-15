<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <div class="d-flex align-items-center gap-2">
            <h3 class="fw-bold mb-0">مسير الرواتب: <?= e($run['run_number']) ?></h3>
            <span class="badge-status <?= $run['status'] ?> fs-6"><?= e($run['status']) ?></span>
        </div>
        <p class="text-muted small mb-0"><?= e($run['title']) ?> &bull; <?= e($period['name_ar']) ?> (<?= $period['start_date'] ?> إلى <?= $period['end_date'] ?>)</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("payroll/runs/{$run['id']}/export") ?>" class="btn btn-outline-success btn-sm rounded-pill px-3">
            <i class="bi bi-file-earmark-excel me-1"></i> تصدير ملف البنك (WPS CSV)
        </a>

        <?php if ($run['status'] === 'calculated' && can('payroll.approve')): ?>
            <form action="<?= url("payroll/runs/{$run['id']}/approve") ?>" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-check2-circle me-1"></i> اعتماد المسير
                </button>
            </form>
        <?php endif; ?>

        <?php if ($run['status'] === 'approved' && can('payroll.lock')): ?>
            <form action="<?= url("payroll/runs/{$run['id']}/lock") ?>" method="POST" onsubmit="return confirm('تحذير أمني: قفل المسير يجعله نهائياً وغير قابل للتعديل أو إعادة الاحتساب إطلاقاً. هل تريد المتابعة؟');" class="d-inline">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger btn-sm rounded-pill px-3 shadow-sm">
                    <i class="bi bi-lock-fill me-1"></i> قفل المسير نهائياً (Lock)
                </button>
            </form>
        <?php endif; ?>

        <?php if ($run['status'] === 'locked'): ?>
            <span class="badge bg-secondary d-flex align-items-center px-3 py-2 rounded-pill">
                <i class="bi bi-shield-lock-fill me-1"></i> مقفل نهائياً وغير قابل للتعديل
            </span>
        <?php endif; ?>
    </div>
</div>

<!-- Run Summary KPI Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="stat-value"><?= $run['employee_count'] ?></div>
                <div class="stat-label">الموظفون المشمولون</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon info"><i class="bi bi-cash"></i></div>
            <div>
                <div class="stat-value"><?= number_format((float)$run['total_gross'], 2) ?></div>
                <div class="stat-label">إجمالي الاستحقاقات (<?= config('payroll.currency_symbol') ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon danger"><i class="bi bi-dash-circle"></i></div>
            <div>
                <div class="stat-value text-danger"><?= number_format((float)$run['total_deductions'], 2) ?></div>
                <div class="stat-label">إجمالي الاستقطاعات والخصم (<?= config('payroll.currency_symbol') ?>)</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="stat-card">
            <div class="stat-icon success"><i class="bi bi-check-all"></i></div>
            <div>
                <div class="stat-value text-success"><?= number_format((float)$run['total_net'], 2) ?></div>
                <div class="stat-label">صافي التحويل البنكي (<?= config('payroll.currency_symbol') ?>)</div>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Items Table -->
<div class="content-card mb-0">
    <div class="card-header">
        <h5><i class="bi bi-list-columns text-primary me-2"></i> كشف استحقاقات واستقطاعات الموظفين التفصيلي</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>الراتب الأساسي</th>
                    <th>البدلات</th>
                    <th>الإضافي</th>
                    <th>الإجمالي</th>
                    <th>خصم الغياب</th>
                    <th>خصم التأخير</th>
                    <th>إجمالي الخصم</th>
                    <th>صافي الراتب</th>
                    <th>قسيمة الراتب</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items as $item): ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark"><?= e($item['first_name_ar'] . ' ' . $item['last_name_ar']) ?></div>
                            <code class="small"><?= e($item['employee_code']) ?></code>
                        </td>
                        <td><?= number_format((float)$item['basic_salary'], 2) ?></td>
                        <td>
                            <?= number_format((float)($item['housing_allowance'] + $item['transport_allowance'] + $item['other_allowances']), 2) ?>
                        </td>
                        <td class="text-primary">
                            <?= number_format((float)$item['overtime_pay'], 2) ?>
                            <small class="text-muted d-block">(<?= $item['overtime_hours'] ?> س)</small>
                        </td>
                        <td class="fw-semibold"><?= number_format((float)$item['gross_salary'], 2) ?></td>
                        <td class="text-danger">
                            <?= (float)$item['absence_deduction'] > 0 ? '-' . number_format((float)$item['absence_deduction'], 2) : '0.00' ?>
                            <?php if ((float)$item['absence_days'] > 0): ?>
                                <small class="text-muted d-block">(<?= $item['absence_days'] ?> يوم)</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-danger">
                            <?= (float)$item['late_deduction'] > 0 ? '-' . number_format((float)$item['late_deduction'], 2) : '0.00' ?>
                            <?php if ((int)$item['late_minutes'] > 0): ?>
                                <small class="text-muted d-block">(<?= $item['late_minutes'] ?> د)</small>
                            <?php endif; ?>
                        </td>
                        <td class="text-danger fw-bold">
                            <?= number_format((float)$item['total_deductions'], 2) ?>
                        </td>
                        <td class="fw-bold text-success fs-6">
                            <?= number_format((float)$item['net_salary'], 2) ?> <?= config('payroll.currency_symbol') ?>
                        </td>
                        <td>
                            <a href="<?= url("payroll/items/{$item['id']}/payslip") ?>" target="_blank" class="btn btn-outline-secondary btn-sm rounded-pill px-2" title="عرض وطباعة القسيمة">
                                <i class="bi bi-printer me-1"></i> قسيمة
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
