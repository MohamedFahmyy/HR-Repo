<?php
$title = __('settings') . ' - Enterprise HR & Payroll';
$pageTitle = __('settings');
$activeMenu = 'settings';
?>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 py-3">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-sliders me-2 text-primary"></i> إعدادات المنظومة المؤسسية
                </h6>
            </div>
            <div class="card-body p-4">
                <form method="POST" action="<?= url('/settings') ?>">
                    <?= csrf_field() ?>

                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">بيانات المنشأة والهوية</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">اسم الشركة (بالعربية)</label>
                            <input type="text" name="settings[company_name_ar]" class="form-control form-control-sm" value="<?= e($settings['company_name_ar'] ?? 'المؤسسة الوطنية المتقدمة') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Company Name (English)</label>
                            <input type="text" name="settings[company_name_en]" class="form-control form-control-sm" value="<?= e($settings['company_name_en'] ?? 'Advanced National Enterprise') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">المنطقة الزمنية الرسمية (Timezone)</label>
                            <input type="text" name="settings[app_timezone]" class="form-control form-control-sm font-monospace" value="<?= e($settings['app_timezone'] ?? 'Asia/Riyadh') ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">العملة الأساسية (Currency)</label>
                            <input type="text" name="settings[currency]" class="form-control form-control-sm" value="<?= e($settings['currency'] ?? 'SAR') ?>" required>
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">سياسات الحضور والربط البيومتري (ZKTeco)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">فترة السماح الافتراضية للتأخير (دقائق)</label>
                            <input type="number" name="settings[default_grace_period_minutes]" class="form-control form-control-sm" value="<?= e($settings['default_grace_period_minutes'] ?? 15) ?>" min="0" max="60">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">نافذة منع تكرار البصمة (دقائق)</label>
                            <input type="number" name="settings[punch_duplicate_window_minutes]" class="form-control form-control-sm" value="<?= e($settings['punch_duplicate_window_minutes'] ?? 5) ?>" min="1" max="30">
                            <small class="text-muted">تجاهل أي بصمة متطابقة على نفس الجهاز خلال هذه المدة</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">منفذ استقبال ZKTeco PUSH / ADMS</label>
                            <input type="text" class="form-control form-control-sm font-monospace bg-light" value="/api/zkteco/iclock/cdata" readonly>
                            <small class="text-muted">قم بتعيين هذا المسار في إعدادات خادم السحابة بجهاز البصمة</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">مهلة اتصال TCP المباشر (ثواني)</label>
                            <input type="number" name="settings[zkteco_socket_timeout]" class="form-control form-control-sm" value="<?= e($settings['zkteco_socket_timeout'] ?? 10) ?>" min="3" max="60">
                        </div>
                    </div>

                    <h6 class="text-uppercase text-muted fw-bold small mb-3 border-bottom pb-2">سياسات مسير الرواتب (Payroll Policies)</h6>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">أيام الشهر المعتمدة لحساب أجر اليوم</label>
                            <input type="number" name="settings[payroll_divisor_days]" class="form-control form-control-sm" value="<?= e($settings['payroll_divisor_days'] ?? 30) ?>" min="28" max="31">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ساعات العمل القياسية لليوم الواحد</label>
                            <input type="number" name="settings[standard_daily_work_hours]" class="form-control form-control-sm" value="<?= e($settings['standard_daily_work_hours'] ?? 8) ?>" min="6" max="10">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">معامل الإضافي العادي</label>
                            <input type="text" name="settings[overtime_rate_regular]" class="form-control form-control-sm" value="<?= e($settings['overtime_rate_regular'] ?? '1.5') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">معامل إضافي أيام الراحة</label>
                            <input type="text" name="settings[overtime_rate_rest_day]" class="form-control form-control-sm" value="<?= e($settings['overtime_rate_rest_day'] ?? '2.0') ?>">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">معامل إضافي العطلات الرسمية</label>
                            <input type="text" name="settings[overtime_rate_holiday]" class="form-control form-control-sm" value="<?= e($settings['overtime_rate_holiday'] ?? '2.5') ?>">
                        </div>
                    </div>

                    <div class="text-end border-top pt-3">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-check-lg me-1"></i> حفظ التعديلات
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h6 class="mb-0 fw-bold text-dark">
                    <i class="bi bi-shield-check me-2 text-primary"></i> سجل التدقيق والرقابة (Audit Trail)
                </h6>
                <span class="badge bg-light text-muted">آخر 15 حركة</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 650px; overflow-y: auto;">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light small text-muted">
                            <tr>
                                <th>الوقت</th>
                                <th>المستخدم</th>
                                <th>الإجراء</th>
                                <th>الهدف</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php if (empty($auditLogs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">لا توجد سجلات تدقيق حتى الآن.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td class="font-monospace text-muted text-nowrap"><?= date('m-d H:i', strtotime($log['created_at'])) ?></td>
                                        <td class="fw-semibold text-dark"><?= e($log['user_name'] ?? 'النظام') ?></td>
                                        <td>
                                            <span class="badge bg-primary-subtle text-primary"><?= e($log['action']) ?></span>
                                        </td>
                                        <td class="text-muted text-truncate" style="max-width: 140px;" title="<?= e($log['details'] ?? '') ?>">
                                            <?= e($log['entity_type'] ?? '') ?> #<?= e($log['entity_id'] ?? '') ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
