<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">إدارة الرواتب ومسيرات الصرف (Payroll Engine)</h3>
        <p class="text-muted small mb-0">احتساب الرواتب التلقائي، البدلات، الاستقطاعات، قفل المسيرات واعتماد التحويلات البنكية</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#createPeriodModal">
            <i class="bi bi-calendar-plus me-1"></i> فتح فترة رواتب جديدة
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#calculatePayrollModal">
            <i class="bi bi-calculator me-1"></i> احتساب مسير رواتب
        </button>
    </div>
</div>

<!-- Payroll Runs List -->
<div class="content-card mb-4">
    <div class="card-header">
        <h5><i class="bi bi-cash-stack text-success me-2"></i> مسيرات الرواتب (Payroll Runs)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>رقم المسير</th>
                    <th>عنوان المسير</th>
                    <th>فترة الاستحقاق</th>
                    <th>عدد الموظفين</th>
                    <th>إجمالي الرواتب</th>
                    <th>إجمالي الخصومات</th>
                    <th>صافي المسير</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($runs)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">لا توجد مسيرات رواتب سابقة.</td></tr>
                <?php else: ?>
                    <?php foreach ($runs as $r): ?>
                        <tr>
                            <td><code><?= e($r['run_number']) ?></code></td>
                            <td class="fw-semibold text-dark"><?= e($r['title']) ?></td>
                            <td><?= e($r['period_name']) ?> <small class="text-muted d-block"><?= $r['start_date'] ?> إلى <?= $r['end_date'] ?></small></td>
                            <td><span class="badge bg-light text-dark border"><?= $r['employee_count'] ?> موظف</span></td>
                            <td class="fw-semibold"><?= number_format((float)$r['total_gross'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                            <td class="text-danger"><?= number_format((float)$r['total_deductions'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                            <td class="fw-bold text-success fs-6"><?= number_format((float)$r['total_net'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                            <td>
                                <span class="badge-status <?= $r['status'] ?>"><?= e($r['status']) ?></span>
                            </td>
                            <td>
                                <a href="<?= url("payroll/runs/{$r['id']}") ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                    <i class="bi bi-eye me-1"></i> التفاصيل
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Periods Card -->
<div class="content-card mb-0">
    <div class="card-header">
        <h5><i class="bi bi-calendar3 text-primary me-2"></i> فترات الرواتب المسجلة</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الرمز</th>
                    <th>اسم الفترة</th>
                    <th>تاريخ البداية</th>
                    <th>تاريخ النهاية</th>
                    <th>موعد الصرف</th>
                    <th>الحالة</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($periods as $prd): ?>
                    <tr>
                        <td><code><?= e($prd['code']) ?></code></td>
                        <td class="fw-semibold"><?= e($prd['name_ar']) ?></td>
                        <td><?= $prd['start_date'] ?></td>
                        <td><?= $prd['end_date'] ?></td>
                        <td><span class="badge bg-light text-dark border"><?= $prd['payment_date'] ?></span></td>
                        <td><span class="badge bg-info-subtle text-info"><?= e($prd['status']) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Create Period -->
<div class="modal fade" id="createPeriodModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('payroll/periods') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">فتح فترة رواتب جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رمز الفترة (الكود)</label>
                        <input type="text" name="code" class="form-control" value="PAY-<?= date('Y-m') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اسم الفترة (بالعربية)</label>
                        <input type="text" name="name_ar" class="form-control" value="مسير رواتب شهر <?= date('m-Y') ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Period Name (English)</label>
                        <input type="text" name="name_en" class="form-control" value="Payroll Period <?= date('m-Y') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">تاريخ البداية</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">تاريخ النهاية</label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-t') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">موعد تحويل وصرف الرواتب</label>
                        <input type="date" name="payment_date" class="form-control" value="<?= date('Y-m-27') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">فتح الفترة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Calculate Payroll -->
<div class="modal fade" id="calculatePayrollModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('payroll/calculate') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">احتساب مسير رواتب جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اختر فترة الرواتب</label>
                        <select name="period_id" class="form-select" required>
                            <?php foreach ($periods as $prd): ?>
                                <option value="<?= $prd['id'] ?>"><?= e($prd['name_ar']) ?> (<?= $prd['start_date'] ?> إلى <?= $prd['end_date'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">عنوان المسير</label>
                        <input type="text" name="title" class="form-control" placeholder="مسير رواتب موظفي المنشأة">
                    </div>
                    <div class="alert alert-info small mb-0">
                        <i class="bi bi-info-circle me-1"></i> سيتم جلب كافة نتائج الحضور المعتمدة، خصومات التأخير والغياب، وساعات العمل الإضافي المحسوبة تلقائياً.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">بدء الاحتساب التلقائي</button>
                </div>
            </form>
        </div>
    </div>
</div>
