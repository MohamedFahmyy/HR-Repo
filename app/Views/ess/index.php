<?php
$title = __('my_profile') . ' - بوابة الموظف الذاتية (ESS)';
$pageTitle = 'بوابة الخدمة الذاتية للموظف (Self-Service)';
$activeMenu = 'ess';
?>

<!-- Header Profile Summary -->
<div class="card border-0 shadow-sm rounded-3 mb-4 bg-gradient" style="background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 68px; height: 68px; font-size: 1.75rem; border: 3px solid rgba(255,255,255,0.2);">
                    <?= mb_substr($employee['name_ar'] ?? 'م', 0, 1, 'UTF-8') ?>
                </div>
                <div>
                    <h4 class="mb-1 fw-bold text-white"><?= e($employee['name_ar'] ?? '') ?> (<?= e($employee['name_en'] ?? '') ?>)</h4>
                    <div class="opacity-75 small d-flex flex-wrap gap-3">
                        <span><i class="bi bi-person-badge me-1"></i> الرقم الوظيفي: <strong><?= e($employee['employee_code'] ?? '') ?></strong></span>
                        <span><i class="bi bi-diagram-3 me-1"></i> <?= e($employee['department_name'] ?? '—') ?></span>
                        <span><i class="bi bi-briefcase me-1"></i> <?= e($employee['position_name'] ?? '—') ?></span>
                        <span><i class="bi bi-geo-alt me-1"></i> فرع: <?= e($employee['branch_name'] ?? 'الرئيسي') ?></span>
                    </div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#requestLeaveModal">
                    <i class="bi bi-calendar-plus me-1"></i> تقديم طلب إجازة
                </button>
                <button type="button" class="btn btn-outline-light btn-sm" data-bs-toggle="modal" data-bs-target="#requestCorrectionModal">
                    <i class="bi bi-clock-history me-1"></i> طلب تصحيح بصمة
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Leave Balances Cards -->
<div class="row g-3 mb-4">
    <?php foreach ($leaveBalances ?? [] as $bal): ?>
        <div class="col-md-3 col-sm-6">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <span class="text-muted small fw-bold"><?= e($bal['leave_type_name'] ?? $bal['name_ar'] ?? 'إجازة') ?></span>
                        <span class="badge bg-primary-subtle text-primary"><?= (int)($bal['year'] ?? date('Y')) ?></span>
                    </div>
                    <div class="d-flex align-items-baseline gap-2">
                        <h3 class="mb-0 fw-bold text-dark"><?= (float)($bal['remaining_days'] ?? $bal['allocated_days'] ?? 0) ?></h3>
                        <small class="text-muted">يوم متبقي</small>
                    </div>
                    <div class="progress mt-2" style="height: 5px;">
                        <?php 
                        $total = (float)($bal['allocated_days'] ?? 30);
                        $used = (float)($bal['used_days'] ?? 0);
                        $percent = $total > 0 ? min(100, round(($used / $total) * 100)) : 0;
                        ?>
                        <div class="progress-bar bg-primary" role="progressbar" style="width: <?= $percent ?>%"></div>
                    </div>
                    <div class="d-flex justify-content-between text-muted small mt-1" style="font-size: 0.75rem;">
                        <span>مستهلك: <?= $used ?></span>
                        <span>مخصص: <?= $total ?></span>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="row g-4">
    <!-- Left Column: Attendance & Punch History -->
    <div class="col-lg-8">
        <!-- Attendance Records -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-calendar-check me-2 text-primary"></i> سجل الحضور والانصراف لشهر <?= date('m/Y') ?>
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>التاريخ</th>
                                <th>الوردية</th>
                                <th>الدخول</th>
                                <th>الخروج</th>
                                <th>ساعات العمل</th>
                                <th>التأخير</th>
                                <th>الإضافي</th>
                                <th>الحالة</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($attendanceRecords)): ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">لا توجد سجلات حضور مسجلة لهذا الشهر.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($attendanceRecords as $att): ?>
                                    <tr>
                                        <td class="fw-semibold text-dark"><?= e($att['date']) ?></td>
                                        <td><?= e($att['shift_name'] ?? 'الصباحية') ?></td>
                                        <td class="text-success font-monospace"><?= !empty($att['check_in']) ? date('H:i', strtotime($att['check_in'])) : '—' ?></td>
                                        <td class="text-danger font-monospace"><?= !empty($att['check_out']) ? date('H:i', strtotime($att['check_out'])) : '—' ?></td>
                                        <td><?= number_format((float)($att['worked_hours'] ?? 0), 2) ?> س</td>
                                        <td>
                                            <?php if (($att['late_minutes'] ?? 0) > 0): ?>
                                                <span class="badge bg-warning-subtle text-warning"><?= (int)$att['late_minutes'] ?> د</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (($att['overtime_hours'] ?? 0) > 0): ?>
                                                <span class="badge bg-success-subtle text-success"><?= number_format((float)$att['overtime_hours'], 2) ?> س</span>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $badge = match($att['status'] ?? 'present') {
                                                'present' => 'bg-success',
                                                'absent' => 'bg-danger',
                                                'late' => 'bg-warning text-dark',
                                                'leave' => 'bg-info',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= e($att['status'] ?? '') ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Raw Biometric Punches History (Transparency) -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-fingerprint me-2 text-primary"></i> البصمات الخام المسجلة من الأجهزة البيومترية (شفافية كاملة)
                </h6>
                <span class="badge bg-light text-muted">آخر 10 بصمات</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0 small">
                        <thead class="table-light text-muted">
                            <tr>
                                <th>وقت البصمة الفعلي</th>
                                <th>الجهاز البيومتري</th>
                                <th>النوع</th>
                                <th>طريقة التحقق</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rawPunches)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">لا توجد بصمات حديثة مسجلة.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rawPunches as $punch): ?>
                                    <tr>
                                        <td class="font-monospace text-dark fw-bold"><?= date('Y-m-d H:i:s', strtotime($punch['punch_time'])) ?></td>
                                        <td><?= e($punch['device_name'] ?? 'جهاز البصمة الرئيسي') ?></td>
                                        <td>
                                            <span class="badge <?= ($punch['punch_type'] ?? '') === 'check_in' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' ?>">
                                                <?= ($punch['punch_type'] ?? '') === 'check_in' ? 'بصمة دخول' : 'بصمة خروج' ?>
                                            </span>
                                        </td>
                                        <td class="text-muted"><?= e($punch['verification_type'] ?? 'بصمة إصبع / وجه') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Right Column: Payslips & Requests -->
    <div class="col-lg-4">
        <!-- Payslips Card -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-file-earmark-spreadsheet me-2 text-primary"></i> كشوف الرواتب الصادرة (Payslips)
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush small">
                    <?php if (empty($payslips)): ?>
                        <div class="p-4 text-center text-muted">
                            لم يتم اعتماد مسيرات رواتب لك بعد.
                        </div>
                    <?php else: ?>
                        <?php foreach ($payslips as $ps): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div>
                                    <div class="fw-bold text-dark"><?= e($ps['period_name'] ?? 'مسير الراتب') ?></div>
                                    <div class="text-muted small">صافي الراتب: <strong class="text-success"><?= number_format((float)($ps['net_salary'] ?? 0), 2) ?> ر.س</strong></div>
                                </div>
                                <a href="<?= url('/payroll/payslip/' . $ps['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-printer me-1"></i> استعراض
                                </a>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- My Leave Requests -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-clock-history me-2 text-primary"></i> طلباتي الأخيرة ومتابعة الاعتماد
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="list-group list-group-flush small">
                    <?php if (empty($myLeaveRequests)): ?>
                        <div class="p-4 text-center text-muted">لا توجد طلبات إجازة سابقة.</div>
                    <?php else: ?>
                        <?php foreach ($myLeaveRequests as $req): ?>
                            <div class="list-group-item py-3">
                                <div class="d-flex justify-content-between align-items-start mb-1">
                                    <span class="fw-bold text-dark"><?= e($req['leave_type_name'] ?? 'إجازة') ?></span>
                                    <span class="badge <?= ($req['status'] ?? '') === 'approved' ? 'bg-success' : (($req['status'] ?? '') === 'rejected' ? 'bg-danger' : 'bg-warning') ?>">
                                        <?= e($req['status'] ?? 'pending') ?>
                                    </span>
                                </div>
                                <div class="text-muted small">
                                    الفترة: <?= e($req['start_date']) ?> إلى <?= e($req['end_date']) ?> (<?= (int)($req['total_days'] ?? $req['days_count'] ?? 0) ?> يوم)
                                </div>
                                <?php if (!empty($req['reason'])): ?>
                                    <div class="text-muted fst-italic mt-1">"<?= e($req['reason']) ?>"</div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Request Leave -->
<div class="modal fade" id="requestLeaveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('/leaves/request') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">تقديم طلب إجازة جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">نوع الإجازة</label>
                        <select name="leave_type_id" class="form-select form-select-sm" required>
                            <?php foreach ($leaveTypes ?? [] as $lt): ?>
                                <option value="<?= $lt['id'] ?>"><?= e($lt['name_ar']) ?> (<?= e($lt['code']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">من تاريخ</label>
                            <input type="date" name="start_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">إلى تاريخ</label>
                            <input type="date" name="end_date" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">سبب الإجازة</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="اذكر سبب طلب الإجازة..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-primary">إرسال الطلب للاعتماد</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Request Attendance Correction -->
<div class="modal fade" id="requestCorrectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="<?= url('/attendance/adjustments/request') ?>">
                <?= csrf_field() ?>
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark">طلب تصحيح بصمة / حضور يدوي</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">تاريخ اليوم المراد تصحيحه</label>
                        <input type="date" name="date" class="form-control form-control-sm" value="<?= date('Y-m-d', strtotime('-1 day')) ?>" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت الدخول الفعلي</label>
                            <input type="time" name="check_in" class="form-control form-control-sm">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت الخروج الفعلي</label>
                            <input type="time" name="check_out" class="form-control form-control-sm">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">سبب عدم التسجيل في الجهاز البيومتري</label>
                        <textarea name="reason" class="form-control form-control-sm" rows="3" placeholder="مثال: عطل في جهاز البصمة، مهمة خارجية ميدانية، نسيان البصمة..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-sm btn-primary">إرسال طلب التصحيح</button>
                </div>
            </form>
        </div>
    </div>
</div>
