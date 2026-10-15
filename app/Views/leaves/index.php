<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">إدارة الإجازات والأرصدة الزمنية</h3>
        <p class="text-muted small mb-0">مراجعة واعتماد طلبات الإجازات ومتابعة القيود المحاسبية لأرصدة الموظفين</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#requestLeaveModal">
            <i class="bi bi-plus-lg me-1"></i> تقديم طلب إجازة
        </button>
    </div>
</div>

<!-- Leave Requests Table Card -->
<div class="content-card mb-4">
    <div class="card-header">
        <h5><i class="bi bi-calendar-event-fill text-primary me-2"></i> طلبات الإجازات المقدمة</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>نوع الإجازة</th>
                    <th>تاريخ البدء</th>
                    <th>تاريخ الانتهاء</th>
                    <th>المدة</th>
                    <th>سبب الإجازة</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                    <tr><td colspan="8" class="text-center py-5 text-muted">لا توجد طلبات إجازة مسجلة.</td></tr>
                <?php else: ?>
                    <?php foreach ($requests as $r): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($r['first_name_ar'] . ' ' . $r['last_name_ar']) ?></div>
                                <code class="small"><?= e($r['employee_code']) ?></code>
                            </td>
                            <td>
                                <span class="badge" style="background-color: <?= $r['color'] ?>20; color: <?= $r['color'] ?>; border: 1px solid <?= $r['color'] ?>40;">
                                    <?= e($r['leave_type_name_ar']) ?>
                                </span>
                            </td>
                            <td><?= $r['start_date'] ?></td>
                            <td><?= $r['end_date'] ?></td>
                            <td class="fw-bold"><?= $r['total_days'] ?> يوم</td>
                            <td class="small" style="max-width: 200px;"><?= e($r['reason']) ?></td>
                            <td>
                                <span class="badge-status <?= $r['status'] ?>"><?= e($r['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($r['status'] === 'pending' && can('leaves.approve')): ?>
                                    <div class="d-flex gap-1">
                                        <form action="<?= url("leaves/{$r['id']}/review") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="اعتماد وخصم الرصيد">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form action="<?= url("leaves/{$r['id']}/review") ?>" method="POST" class="d-inline">
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

<!-- Leave Balances Ledger Table -->
<div class="content-card mb-0">
    <div class="card-header">
        <h5><i class="bi bi-wallet2 text-success me-2"></i> سجل أرصدة الإجازات للعام الحالي (<?= date('Y') ?>)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>نوع الإجازة</th>
                    <th>الرصيد الافتتاحي</th>
                    <th>المستخدم</th>
                    <th>الرصيد المتبقي المتاح</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($balances as $b): ?>
                    <tr>
                        <td class="fw-semibold"><?= e($b['employee_name']) ?> <small class="text-muted">(<?= e($b['employee_code']) ?>)</small></td>
                        <td>
                            <span class="badge" style="background-color: <?= $b['color'] ?>20; color: <?= $b['color'] ?>;">
                                <?= e($b['leave_name']) ?>
                            </span>
                        </td>
                        <td><?= $b['opening_balance'] ?> يوم</td>
                        <td class="text-danger"><?= $b['used_days'] ?> يوم</td>
                        <td class="fw-bold text-success fs-6"><?= $b['current_balance'] ?> يوم</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Request Leave -->
<div class="modal fade" id="requestLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('leaves') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تقديم طلب إجازة جديد</h5>
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
                        <label class="form-label small fw-semibold">نوع الإجازة</label>
                        <select name="leave_type_id" class="form-select" required>
                            <?php foreach ($leaveTypes as $lt): ?>
                                <option value="<?= $lt['id'] ?>"><?= e($lt['name_ar']) ?> (<?= $lt['default_days_per_year'] ?> يوم/سنة)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">تاريخ البدء</label>
                            <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">تاريخ الانتهاء</label>
                            <input type="date" name="end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 days')) ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">سبب الإجازة وملاحظات</label>
                        <textarea name="reason" class="form-control" rows="3" placeholder="اكتب سبب طلب الإجازة..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">إرسال الطلب</button>
                </div>
            </form>
        </div>
    </div>
</div>
