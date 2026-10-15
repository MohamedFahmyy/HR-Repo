<?php
$title = __('reports') . ' - Enterprise HR & Payroll';
$pageTitle = __('reports');
$activeMenu = 'reports';

$currentReport = $reportType ?? 'attendance';
$startDate = $filters['start_date'] ?? date('Y-m-01');
$endDate = $filters['end_date'] ?? date('Y-m-d');
?>

<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <form method="GET" action="<?= url('/reports') ?>" class="row g-3 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-bold"><?= __('report_type') ?? 'نوع التقرير' ?></label>
                        <select name="type" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="attendance" <?= $currentReport === 'attendance' ? 'selected' : '' ?>>📊 تقرير الحضور والانصراف التفصيلي</option>
                            <option value="lateness" <?= $currentReport === 'lateness' ? 'selected' : '' ?>>⏳ تقرير التأخيرات والخروج المبكر</option>
                            <option value="absences" <?= $currentReport === 'absences' ? 'selected' : '' ?>>❌ تقرير الغياب والانقطاع</option>
                            <option value="overtime" <?= $currentReport === 'overtime' ? 'selected' : '' ?>>⏰ تقرير ساعات العمل الإضافي</option>
                            <option value="leaves" <?= $currentReport === 'leaves' ? 'selected' : '' ?>>🏖️ تقرير الإجازات والأرصدة</option>
                            <option value="sync_logs" <?= $currentReport === 'sync_logs' ? 'selected' : '' ?>>🔄 سجلات مزامنة الأجهزة البيومترية</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted fw-bold"><?= __('from_date') ?? 'من تاريخ' ?></label>
                        <input type="date" name="start_date" class="form-control form-control-sm" value="<?= e($startDate) ?>">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-muted fw-bold"><?= __('to_date') ?? 'إلى تاريخ' ?></label>
                        <input type="date" name="end_date" class="form-control form-control-sm" value="<?= e($endDate) ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small text-muted fw-bold"><?= __('department') ?? 'القسم' ?></label>
                        <select name="department_id" class="form-select form-select-sm">
                            <option value="">-- جميع الأقسام --</option>
                            <?php foreach ($departments ?? [] as $dept): ?>
                                <option value="<?= $dept['id'] ?>" <?= ($filters['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                                    <?= e($dept['name_ar']) ?> (<?= e($dept['code']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 d-flex gap-2">
                        <button type="submit" class="btn btn-sm btn-primary flex-fill">
                            <i class="bi bi-filter me-1"></i> <?= __('filter') ?? 'تصفية' ?>
                        </button>
                        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary" title="طباعة">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="card border-0 shadow-sm rounded-3">
    <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
        <h6 class="mb-0 fw-bold text-dark">
            <i class="bi bi-table me-2 text-primary"></i>
            نتائج التقرير: 
            <span class="badge bg-primary-subtle text-primary ms-1"><?= count($reportData ?? []) ?> سجل</span>
        </h6>
        <div>
            <a href="<?= url('/reports/export?type=' . urlencode($currentReport) . '&start_date=' . urlencode($startDate) . '&end_date=' . urlencode($endDate) . '&format=csv') ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-spreadsheet me-1"></i> تصدير CSV
            </a>
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <?php if (empty($reportData)): ?>
                <div class="p-5 text-center text-muted">
                    <i class="bi bi-folder2-open display-4 opacity-50 mb-3 d-block"></i>
                    لا توجد بيانات متاحة لهذا النطاق من التواريخ والتصفيات المحددة.
                </div>
            <?php else: ?>
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <?php if ($currentReport === 'attendance' || $currentReport === 'lateness' || $currentReport === 'absences' || $currentReport === 'overtime'): ?>
                            <tr>
                                <th>التاريخ</th>
                                <th>الموظف</th>
                                <th>القسم</th>
                                <th>الوردية</th>
                                <th>الدخول</th>
                                <th>الخروج</th>
                                <th>ساعات العمل</th>
                                <th>التأخير</th>
                                <th>الإضافي</th>
                                <th>الحالة</th>
                            </tr>
                        <?php elseif ($currentReport === 'leaves'): ?>
                            <tr>
                                <th>الموظف</th>
                                <th>نوع الإجازة</th>
                                <th>من تاريخ</th>
                                <th>إلى تاريخ</th>
                                <th>عدد الأيام</th>
                                <th>الحالة</th>
                                <th>الرصيد المتبقي</th>
                            </tr>
                        <?php elseif ($currentReport === 'sync_logs'): ?>
                            <tr>
                                <th>وقت المزامنة</th>
                                <th>الجهاز</th>
                                <th>نوع المزامنة</th>
                                <th>الحالة</th>
                                <th>الحركات المسترجعة</th>
                                <th>الحركات الجديدة</th>
                                <th>رسالة الحالة</th>
                            </tr>
                        <?php endif; ?>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                            <?php if ($currentReport === 'attendance' || $currentReport === 'lateness' || $currentReport === 'absences' || $currentReport === 'overtime'): ?>
                                <tr>
                                    <td class="fw-semibold text-dark"><?= e($row['date'] ?? '') ?></td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($row['employee_name'] ?? $row['name_ar'] ?? '') ?></div>
                                        <div class="small text-muted"><?= e($row['employee_code'] ?? '') ?></div>
                                    </td>
                                    <td><?= e($row['department_name'] ?? '—') ?></td>
                                    <td><?= e($row['shift_name'] ?? 'الوردية القياسية') ?></td>
                                    <td class="text-success font-monospace"><?= !empty($row['check_in']) ? date('H:i', strtotime($row['check_in'])) : '—' ?></td>
                                    <td class="text-danger font-monospace"><?= !empty($row['check_out']) ? date('H:i', strtotime($row['check_out'])) : '—' ?></td>
                                    <td><?= number_format((float)($row['worked_hours'] ?? 0), 2) ?> س</td>
                                    <td>
                                        <?php if (($row['late_minutes'] ?? 0) > 0): ?>
                                            <span class="badge bg-warning-subtle text-warning"><?= (int)$row['late_minutes'] ?> د</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (($row['overtime_hours'] ?? 0) > 0): ?>
                                            <span class="badge bg-success-subtle text-success"><?= number_format((float)$row['overtime_hours'], 2) ?> س</span>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $statusBadge = match($row['status'] ?? 'present') {
                                            'present' => 'bg-success text-white',
                                            'absent' => 'bg-danger text-white',
                                            'late' => 'bg-warning text-dark',
                                            'leave' => 'bg-info text-white',
                                            'holiday' => 'bg-primary text-white',
                                            'rest_day' => 'bg-secondary text-white',
                                            default => 'bg-light text-dark'
                                        };
                                        ?>
                                        <span class="badge <?= $statusBadge ?>"><?= e($row['status'] ?? '') ?></span>
                                    </td>
                                </tr>
                            <?php elseif ($currentReport === 'leaves'): ?>
                                <tr>
                                    <td>
                                        <div class="fw-bold text-dark"><?= e($row['employee_name'] ?? '') ?></div>
                                        <div class="small text-muted"><?= e($row['employee_code'] ?? '') ?></div>
                                    </td>
                                    <td><?= e($row['leave_type_name'] ?? '') ?></td>
                                    <td><?= e($row['start_date'] ?? '') ?></td>
                                    <td><?= e($row['end_date'] ?? '') ?></td>
                                    <td><span class="badge bg-light text-dark"><?= (int)($row['days_count'] ?? 0) ?> يوم</span></td>
                                    <td>
                                        <span class="badge <?= ($row['status'] ?? '') === 'approved' ? 'bg-success' : 'bg-warning' ?>">
                                            <?= e($row['status'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td class="fw-bold text-primary"><?= (float)($row['remaining_balance'] ?? 0) ?> يوم</td>
                                </tr>
                            <?php elseif ($currentReport === 'sync_logs'): ?>
                                <tr>
                                    <td class="font-monospace text-muted"><?= e($row['started_at'] ?? $row['created_at'] ?? '') ?></td>
                                    <td class="fw-bold text-dark"><?= e($row['device_name'] ?? ('جهاز #' . ($row['device_id'] ?? ''))) ?></td>
                                    <td><?= e($row['sync_type'] ?? 'auto') ?></td>
                                    <td>
                                        <span class="badge <?= ($row['status'] ?? '') === 'success' ? 'bg-success' : 'bg-danger' ?>">
                                            <?= e($row['status'] ?? '') ?>
                                        </span>
                                    </td>
                                    <td><?= (int)($row['records_fetched'] ?? 0) ?></td>
                                    <td><span class="fw-bold text-success">+<?= (int)($row['records_inserted'] ?? 0) ?></span></td>
                                    <td class="small text-muted"><?= e($row['error_message'] ?? 'مكتمل بنجاح') ?></td>
                                </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
