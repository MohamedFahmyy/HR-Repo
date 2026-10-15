<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">إدارة ساعات العمل الإضافي (Overtime)</h3>
        <p class="text-muted small mb-0">اعتماد ومطابقة ساعات العمل الإضافي المنجزة خارج الدوام الرسمي</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestOvertimeModal">
            <i class="bi bi-plus-lg me-1"></i> تسجيل طلب عمل إضافي
        </button>
    </div>
</div>

<div class="content-card mb-0">
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>التاريخ</th>
                    <th>وقت البدء</th>
                    <th>وقت الانتهاء</th>
                    <th>الساعات المنجزة</th>
                    <th>نوع الإضافي</th>
                    <th>المعامل</th>
                    <th>السبب والمهام</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="10" class="text-center py-5 text-muted">لا توجد طلبات عمل إضافي مسجلة.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($r['first_name_ar'] . ' ' . $r['last_name_ar']) ?></div>
                                <code class="small"><?= e($r['employee_code']) ?></code>
                            </td>
                            <td><?= $r['date'] ?></td>
                            <td><?= substr($r['start_time'], 0, 5) ?></td>
                            <td><?= substr($r['end_time'], 0, 5) ?></td>
                            <td class="fw-bold text-primary fs-6"><?= number_format((float)$r['hours'], 2) ?> س</td>
                            <td>
                                <?php if ($r['overtime_type'] === 'holiday'): ?>
                                    <span class="badge bg-danger-subtle text-danger">عطلة رسمية</span>
                                <?php elseif ($r['overtime_type'] === 'rest_day'): ?>
                                    <span class="badge bg-warning-subtle text-warning">راحة أسبوعية</span>
                                <?php else: ?>
                                    <span class="badge bg-light text-dark border">يوم عمل عادي</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= $r['multiplier'] ?>x</span></td>
                            <td class="small" style="max-width: 200px;"><?= e($r['reason']) ?></td>
                            <td>
                                <span class="badge-status <?= $r['status'] ?>"><?= e($r['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending' && can('overtime.approve')): ?>
                                    <div class="d-flex gap-1">
                                        <form action="<?= url("overtime/{$r['id']}/review") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="اعتماد">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form action="<?= url("overtime/{$r['id']}/review") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" title="رفض">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small"><?= e($r['approver_name'] ? 'معتمد من: ' . $r['approver_name'] : '--') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Request Overtime -->
<div class="modal fade" id="requestOvertimeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('overtime') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تسجيل ساعات عمل إضافي لموظف</h5>
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
                        <label class="form-label small fw-semibold">تاريخ العمل الإضافي</label>
                        <input type="date" name="date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت البدء</label>
                            <input type="time" name="start_time" class="form-control" value="17:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت الانتهاء</label>
                            <input type="time" name="end_time" class="form-control" value="20:00" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">نوع يوم العمل</label>
                        <select name="overtime_type" class="form-select">
                            <option value="regular">يوم عمل عادي (معامل 1.5x)</option>
                            <option value="rest_day">يوم راحة أسبوعية (معامل 2.0x)</option>
                            <option value="holiday">عطلة رسمية (معامل 2.5x)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المهام المنجزة ومبرر العمل الإضافي</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="اكتب وصفاً للأعمال والمهام المنجزة..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>
