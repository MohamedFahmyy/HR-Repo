<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">طلبات تصحيح الحضور والانصراف</h3>
        <p class="text-muted small mb-0">مراجعة واعتماد طلبات الموظفين الخاصة بالبصمات المفقودة، تأخيرات مبررة أو نزاعات السجلات</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('attendance') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-right me-1"></i> العودة لسجل الحضور
        </a>
    </div>
</div>

<!-- Status Filter Tabs -->
<ul class="nav nav-pills mb-4 gap-2">
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 <?= $currentStatus === 'all' ? 'active' : '' ?>" href="<?= url('attendance/adjustments?status=all') ?>">الكل</a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 <?= $currentStatus === 'pending' ? 'active' : '' ?>" href="<?= url('attendance/adjustments?status=pending') ?>">قيد الانتظار</a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 <?= $currentStatus === 'approved' ? 'active' : '' ?>" href="<?= url('attendance/adjustments?status=approved') ?>">معتمدة</a>
    </li>
    <li class="nav-item">
        <a class="nav-link rounded-pill px-3 <?= $currentStatus === 'rejected' ? 'active' : '' ?>" href="<?= url('attendance/adjustments?status=rejected') ?>">مرفوضة</a>
    </li>
</ul>

<div class="content-card mb-0">
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>التاريخ</th>
                    <th>نوع التصحيح</th>
                    <th>الحضور المطلوب</th>
                    <th>الانصراف المطلوب</th>
                    <th>سبب الطلب</th>
                    <th>مقدم الطلب</th>
                    <th>الحالة</th>
                    <th>الإجراء</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($adjustments)): ?>
                    <tr><td colspan="9" class="text-center py-5 text-muted">لا توجد طلبات تصحيح مطابقة.</td></tr>
                <?php else: ?>
                    <?php foreach ($adjustments as $adj): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold"><?= e($adj['first_name_ar'] . ' ' . $adj['last_name_ar']) ?></div>
                                <code class="small"><?= e($adj['employee_code']) ?></code>
                            </td>
                            <td><?= $adj['date'] ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($adj['adjustment_type']) ?></span></td>
                            <td><?= $adj['requested_check_in'] ? date('H:i', strtotime($adj['requested_check_in'])) : '--:--' ?></td>
                            <td><?= $adj['requested_check_out'] ? date('H:i', strtotime($adj['requested_check_out'])) : '--:--' ?></td>
                            <td class="small" style="max-width: 250px;"><?= e($adj['reason']) ?></td>
                            <td><span class="small text-muted"><?= e($adj['requester_name']) ?></span></td>
                            <td>
                                <span class="badge-status <?= $adj['status'] ?>"><?= e($adj['status']) ?></span>
                            </td>
                            <td>
                                <?php if ($adj['status'] === 'pending'): ?>
                                    <div class="d-flex gap-1">
                                        <form action="<?= url("attendance/adjustments/{$adj['id']}/review") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="approve">
                                            <button type="submit" class="btn btn-success btn-sm" title="اعتماد">
                                                <i class="bi bi-check-lg"></i>
                                            </button>
                                        </form>
                                        <form action="<?= url("attendance/adjustments/{$adj['id']}/review") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger btn-sm" title="رفض">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small">تم المراجعة بواسطة <?= e($adj['reviewer_name'] ?? 'النظام') ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
