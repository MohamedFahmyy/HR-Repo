<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">سجلات الحضور والانصراف اليومية</h3>
        <p class="text-muted small mb-0">عرض تفصيلي لبصمات الحضور، الانصراف، ساعات العمل المحسوبة وحالات التأخير والغياب</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#recalculateModal">
            <i class="bi bi-arrow-repeat me-1"></i> إعادة احتساب فترة
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#manualPunchModal">
            <i class="bi bi-plus-lg me-1"></i> تسجيل يدوي
        </button>
    </div>
</div>

<!-- Date & Filters Bar -->
<div class="content-card mb-4">
    <div class="card-body">
        <form action="<?= url('attendance') ?>" method="GET" class="row g-3">
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">تاريخ السجل</label>
                <input type="date" name="date" class="form-control form-control-sm" value="<?= e($date) ?>" onchange="this.form.submit()">
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">القسم</label>
                <select name="department_id" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $d): ?>
                        <option value="<?= $d['id'] ?>" <?= ($filters['department_id'] ?? '') == $d['id'] ? 'selected' : '' ?>><?= e($d['name_ar']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">الحالة</label>
                <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">جميع الحالات</option>
                    <option value="present" <?= ($filters['status'] ?? '') === 'present' ? 'selected' : '' ?>>حاضر (Present)</option>
                    <option value="late" <?= ($filters['status'] ?? '') === 'late' ? 'selected' : '' ?>>متأخر (Late)</option>
                    <option value="absent" <?= ($filters['status'] ?? '') === 'absent' ? 'selected' : '' ?>>غائب (Absent)</option>
                    <option value="on_leave" <?= ($filters['status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>في إجازة (On Leave)</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end">
                <a href="<?= url('attendance') ?>" class="btn btn-outline-secondary btn-sm w-100">عرض اليوم الحالي</a>
            </div>
        </form>
    </div>
</div>

<!-- Daily Attendance Table -->
<div class="content-card mb-0">
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الرقم</th>
                    <th>الموظف</th>
                    <th>القسم</th>
                    <th>الوردية</th>
                    <th>تسجيل الحضور</th>
                    <th>تسجيل الانصراف</th>
                    <th>ساعات العمل</th>
                    <th>التأخير</th>
                    <th>العمل الإضافي</th>
                    <th>الحالة</th>
                    <th>ملاحظات وتدقيق</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($records)): ?>
                    <tr><td colspan="11" class="text-center py-5 text-muted">لا توجد سجلات حضور مسجلة لهذا التاريخ.</td></tr>
                <?php else: ?>
                    <?php foreach ($records as $r): ?>
                        <tr>
                            <td><code><?= e($r['employee_code']) ?></code></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($r['first_name_ar'] . ' ' . $r['last_name_ar']) ?></div>
                                <div class="text-muted small"><?= e($r['first_name_en'] . ' ' . $r['last_name_en']) ?></div>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($r['department_name'] ?? '--') ?></span></td>
                            <td><?= e($r['shift_name'] ?? 'العامة') ?></td>
                            <td class="fw-semibold">
                                <?= $r['check_in'] ? date('H:i:s', strtotime($r['check_in'])) : '<span class="text-muted">--:--</span>' ?>
                            </td>
                            <td class="fw-semibold">
                                <?= $r['check_out'] ? date('H:i:s', strtotime($r['check_out'])) : '<span class="text-muted">--:--</span>' ?>
                            </td>
                            <td><?= number_format((float)$r['work_hours'], 2) ?> س</td>
                            <td class="<?= (int)$r['late_minutes'] > 0 ? 'text-warning fw-bold' : '' ?>">
                                <?= (int)$r['late_minutes'] > 0 ? $r['late_minutes'] . ' د' : '0' ?>
                            </td>
                            <td class="<?= (float)$r['overtime_hours'] > 0 ? 'text-primary fw-bold' : '' ?>">
                                <?= (float)$r['overtime_hours'] > 0 ? number_format((float)$r['overtime_hours'], 2) . ' س' : '0' ?>
                            </td>
                            <td>
                                <span class="badge-status <?= $r['status'] ?>"><?= e($r['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($r['is_manually_adjusted']): ?>
                                    <span class="badge bg-info-subtle text-info"><i class="bi bi-pencil-square"></i> معدل يدوياً</span>
                                <?php endif; ?>
                                <small class="text-muted d-block"><?= e($r['notes'] ?? '') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Manual Punch -->
<div class="modal fade" id="manualPunchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('attendance/manual') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تسجيل حضور يدوي لموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الموظف</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">اختر الموظف...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= e($e['employee_code']) ?> - <?= e($e['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">التاريخ</label>
                        <input type="date" name="date" class="form-control" value="<?= $date ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت الحضور</label>
                            <input type="time" name="check_in" class="form-control" value="08:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت الانصراف</label>
                            <input type="time" name="check_out" class="form-control" value="17:00">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ الحضور</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Recalculate Period -->
<div class="modal fade" id="recalculateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('attendance/calculate') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">إعادة احتساب الحضور لفترة زمنية</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small">يقوم هذا الإجراء بإعادة قراءة بصمات أجهزة ZKTeco وتطبيق سياسات الورديات والإجازات والتأخير على جميع الموظفين.</p>
                    <div class="row g-2">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">من تاريخ</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-01') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">إلى تاريخ</label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">بدء الاحتساب</button>
                </div>
            </form>
        </div>
    </div>
</div>
