<div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-3">
        <div class="rounded-circle bg-primary text-white fw-bold d-flex align-items-center justify-content-center fs-3 shadow-sm" style="width: 64px; height: 64px;">
            <?= mb_substr($employee['first_name_ar'], 0, 1) ?>
        </div>
        <div>
            <div class="d-flex align-items-center gap-2">
                <h3 class="fw-bold mb-0"><?= e($employee['first_name_ar'] . ' ' . $employee['last_name_ar']) ?></h3>
                <?php if ($employee['employment_status'] === 'active'): ?>
                    <span class="badge bg-success-subtle text-success">نشط</span>
                <?php else: ?>
                    <span class="badge bg-secondary-subtle text-secondary"><?= e($employee['employment_status']) ?></span>
                <?php endif; ?>
            </div>
            <p class="text-muted small mb-0">
                <span><?= e($employee['first_name_en'] . ' ' . $employee['last_name_en']) ?></span> &bull;
                <code><?= e($employee['employee_code']) ?></code> &bull;
                <span><?= e($employee['position_title_ar'] ?? 'غير محدد') ?></span> &bull;
                <span><?= e($employee['department_name_ar'] ?? 'غير محدد') ?></span>
            </p>
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url("employees/{$employee['id']}/edit") ?>" class="btn btn-outline-primary btn-sm rounded-pill px-3">
            <i class="bi bi-pencil me-1"></i> تعديل البيانات
        </a>
        <form action="<?= url("employees/{$employee['id']}/delete") ?>" method="POST" onsubmit="return confirm('هل أنت متأكد من أرشفة هذا الموظف؟ لن يتم حذفه نهائياً حفاظاً على سجلات الحضور والرواتب.');" class="d-inline">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-outline-danger btn-sm rounded-pill px-3">
                <i class="bi bi-archive me-1"></i> أرشفة
            </button>
        </form>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-pills mb-4 gap-2" id="employeeProfileTabs" role="tablist">
    <li class="nav-item">
        <button class="nav-link active rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-overview">
            <i class="bi bi-info-circle me-1"></i> البيانات الشخصية والوظيفية
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-financials">
            <i class="bi bi-cash-stack me-1"></i> الراتب والبدلات
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-attendance">
            <i class="bi bi-calendar2-check me-1"></i> سجل الحضور والانصراف
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-leaves">
            <i class="bi bi-calendar-range me-1"></i> رصيد الإجازات
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-biometric">
            <i class="bi bi-fingerprint me-1"></i> أجهزة البصمة المرتبطة
        </button>
    </li>
    <li class="nav-item">
        <button class="nav-link rounded-pill px-3" data-bs-toggle="pill" data-bs-target="#tab-payslips">
            <i class="bi bi-receipt me-1"></i> قسائم الرواتب
        </button>
    </li>
</ul>

<div class="tab-content">
    <!-- Tab 1: Overview -->
    <div class="tab-pane fade show active" id="tab-overview">
        <div class="row g-4">
            <div class="col-lg-6">
                <div class="content-card mb-0">
                    <div class="card-header">
                        <h5>البيانات الشخصية والاتصال</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless m-0">
                            <tr><td class="text-muted" style="width: 40%;">رقم الهوية الوطنية / الإقامة:</td><td class="fw-semibold"><code><?= e($employee['national_id']) ?></code></td></tr>
                            <tr><td class="text-muted">رقم جواز السفر:</td><td class="fw-semibold"><?= e($employee['passport_number'] ?: 'غير مسجل') ?></td></tr>
                            <tr><td class="text-muted">البريد الإلكتروني:</td><td class="fw-semibold"><?= e($employee['email']) ?></td></tr>
                            <tr><td class="text-muted">رقم الهاتف:</td><td class="fw-semibold" dir="ltr"><?= e($employee['phone']) ?></td></tr>
                            <tr><td class="text-muted">الجنسية:</td><td class="fw-semibold"><?= e($employee['nationality'] ?? 'سعودي') ?></td></tr>
                            <tr><td class="text-muted">الجنس:</td><td class="fw-semibold"><?= $employee['gender'] === 'female' ? 'أنثى' : 'ذكر' ?></td></tr>
                            <tr><td class="text-muted">الحالة الاجتماعية:</td><td class="fw-semibold"><?= e($employee['marital_status']) ?></td></tr>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="content-card mb-0">
                    <div class="card-header">
                        <h5>البيانات الوظيفية والتعاقدية</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless m-0">
                            <tr><td class="text-muted" style="width: 40%;">الفرع:</td><td class="fw-semibold"><?= e($employee['branch_name_ar'] ?? 'المقر الرئيسي') ?></td></tr>
                            <tr><td class="text-muted">القسم:</td><td class="fw-semibold"><?= e($employee['department_name_ar'] ?? 'غير محدد') ?></td></tr>
                            <tr><td class="text-muted">المسمى الوظيفي:</td><td class="fw-semibold"><?= e($employee['position_title_ar'] ?? 'غير محدد') ?></td></tr>
                            <tr><td class="text-muted">المدير المباشر:</td><td class="fw-semibold"><?= e($employee['manager_name_ar'] ?: 'لا يوجد') ?></td></tr>
                            <tr><td class="text-muted">تاريخ التعيين:</td><td class="fw-semibold"><?= e($employee['hire_date']) ?></td></tr>
                            <tr><td class="text-muted">فترة التجربة حتى:</td><td class="fw-semibold"><?= e($employee['probation_end_date'] ?: 'مكتملة') ?></td></tr>
                            <tr><td class="text-muted">الوردية المعتمدة:</td><td class="fw-semibold text-primary"><?= e($employee['shift_name_ar'] ?? 'الوردية القياسية') ?> (<?= substr($employee['shift_start'] ?? '08:00', 0, 5) ?> - <?= substr($employee['shift_end'] ?? '17:00', 0, 5) ?>)</td></tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 2: Financials -->
    <div class="tab-pane fade" id="tab-financials">
        <div class="row g-4">
            <div class="col-lg-7">
                <div class="content-card mb-0">
                    <div class="card-header">
                        <h5>مكونات الراتب الشهري</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-custom mb-3">
                            <thead>
                                <tr><th>البند المالي</th><th class="text-end">القيمة</th></tr>
                            </thead>
                            <tbody>
                                <tr><td>الراتب الأساسي (Basic Salary)</td><td class="text-end fw-bold"><?= number_format((float)$employee['basic_salary'], 2) ?> <?= config('payroll.currency_symbol') ?></td></tr>
                                <tr><td>بدل سكن (Housing Allowance)</td><td class="text-end"><?= number_format((float)$employee['housing_allowance'], 2) ?> <?= config('payroll.currency_symbol') ?></td></tr>
                                <tr><td>بدل مواصلات (Transport Allowance)</td><td class="text-end"><?= number_format((float)$employee['transport_allowance'], 2) ?> <?= config('payroll.currency_symbol') ?></td></tr>
                                <tr><td>بدلات أخرى إضافية</td><td class="text-end"><?= number_format((float)$employee['other_allowances'], 2) ?> <?= config('payroll.currency_symbol') ?></td></tr>
                                <tr class="table-light fw-bold">
                                    <td>إجمالي الراتب التعاقدي (Gross Salary)</td>
                                    <td class="text-end text-success fs-5">
                                        <?= number_format((float)($employee['basic_salary'] + $employee['housing_allowance'] + $employee['transport_allowance'] + $employee['other_allowances']), 2) ?> <?= config('payroll.currency_symbol') ?>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="content-card mb-0">
                    <div class="card-header">
                        <h5>البيانات البنكية لتحويل الراتب</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <span class="text-muted small d-block">اسم البنك المعتمد:</span>
                            <span class="fw-bold fs-6"><?= e($employee['bank_name'] ?: 'غير مسجل') ?></span>
                        </div>
                        <div class="mb-3">
                            <span class="text-muted small d-block">رقم الحساب الدولي (IBAN):</span>
                            <code class="fs-6"><?= e($employee['iban'] ?: 'غير مسجل') ?></code>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tab 3: Attendance -->
    <div class="tab-pane fade" id="tab-attendance">
        <div class="content-card mb-0">
            <div class="card-header">
                <h5>سجل الحضور والانصراف الأخير</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>التاريخ</th>
                            <th>الوردية</th>
                            <th>وقت الحضور</th>
                            <th>وقت الانصراف</th>
                            <th>ساعات العمل</th>
                            <th>التأخير</th>
                            <th>العمل الإضافي</th>
                            <th>الحالة</th>
                            <th>ملاحظات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($recentAttendance)): ?>
                            <tr><td colspan="9" class="text-center py-4 text-muted">لا توجد سجلات حضور مسجلة لهذا الموظف.</td></tr>
                        <?php else: ?>
                            <?php foreach ($recentAttendance as $att): ?>
                                <tr>
                                    <td class="fw-semibold"><?= $att['date'] ?></td>
                                    <td><?= e($att['shift_name'] ?? 'عام') ?></td>
                                    <td><?= $att['check_in'] ? date('H:i:s', strtotime($att['check_in'])) : '--:--' ?></td>
                                    <td><?= $att['check_out'] ? date('H:i:s', strtotime($att['check_out'])) : '--:--' ?></td>
                                    <td><?= number_format((float)$att['work_hours'], 2) ?> س</td>
                                    <td class="<?= (int)$att['late_minutes'] > 0 ? 'text-warning fw-bold' : '' ?>"><?= $att['late_minutes'] ?> د</td>
                                    <td class="<?= (float)$att['overtime_hours'] > 0 ? 'text-primary fw-bold' : '' ?>"><?= number_format((float)$att['overtime_hours'], 2) ?> س</td>
                                    <td>
                                        <span class="badge-status <?= $att['status'] ?>"><?= e($att['status']) ?></span>
                                    </td>
                                    <td class="text-muted small"><?= e($att['notes'] ?? '') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 4: Leaves -->
    <div class="tab-pane fade" id="tab-leaves">
        <div class="row g-4">
            <?php foreach ($leaveBalances as $b): ?>
                <div class="col-md-4">
                    <div class="stat-card">
                        <div class="stat-icon" style="background-color: <?= $b['color'] ?>20; color: <?= $b['color'] ?>;">
                            <i class="bi bi-calendar2-heart"></i>
                        </div>
                        <div>
                            <div class="stat-value" style="color: <?= $b['color'] ?>;"><?= $b['current_balance'] ?> يوم</div>
                            <div class="stat-label"><?= e($b['leave_type_name_ar']) ?> (مستخدم: <?= $b['used_days'] ?>)</div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Tab 5: Biometric Devices -->
    <div class="tab-pane fade" id="tab-biometric">
        <div class="content-card mb-0">
            <div class="card-header">
                <h5>أجهزة البصمة المرتبطة بالموظف</h5>
                <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#mapDeviceModal">
                    <i class="bi bi-plus-lg me-1"></i> ربط جهاز جديد
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>الجهاز</th>
                            <th>عنوان IP</th>
                            <th>البروتوكول</th>
                            <th>رقم الموظف في الجهاز (PIN)</th>
                            <th>تاريخ الربط</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($deviceMappings)): ?>
                            <tr><td colspan="5" class="text-center py-4 text-muted">لم يتم ربط الموظف بأي جهاز بصمة بعد.</td></tr>
                        <?php else: ?>
                            <?php foreach ($deviceMappings as $m): ?>
                                <tr>
                                    <td class="fw-semibold"><?= e($m['device_name']) ?></td>
                                    <td><code><?= e($m['ip_address']) ?></code></td>
                                    <td><span class="badge bg-light text-dark border"><?= e($m['protocol']) ?></span></td>
                                    <td><span class="badge bg-primary fs-6 px-3 py-1"><?= e($m['device_user_id']) ?></span></td>
                                    <td><?= $m['created_at'] ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Tab 6: Payslips -->
    <div class="tab-pane fade" id="tab-payslips">
        <div class="content-card mb-0">
            <div class="card-header">
                <h5>قسائم الرواتب المعتمدة</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>رقم القسيمة</th>
                            <th>فترة الرواتب</th>
                            <th>الراتب الإجمالي</th>
                            <th>إجمالي الخصومات</th>
                            <th>صافي الراتب</th>
                            <th>حالة الصرف</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($payslips)): ?>
                            <tr><td colspan="7" class="text-center py-4 text-muted">لا توجد قسائم رواتب معتمدة صادرة لهذا الموظف حتى الآن.</td></tr>
                        <?php else: ?>
                            <?php foreach ($payslips as $ps): ?>
                                <tr>
                                    <td><code><?= e($ps['payslip_number']) ?></code></td>
                                    <td class="fw-semibold"><?= e($ps['period_name_ar']) ?></td>
                                    <td><?= number_format((float)$ps['gross_salary'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                                    <td class="text-danger"><?= number_format((float)$ps['total_deductions'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                                    <td class="fw-bold text-success fs-6"><?= number_format((float)$ps['net_salary'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                                    <td><span class="badge bg-success-subtle text-success">معتمد ومصروف</span></td>
                                    <td>
                                        <a href="<?= url("payroll/items/{$ps['payroll_item_id']}/payslip") ?>" target="_blank" class="btn btn-outline-primary btn-sm rounded-pill px-3">
                                            <i class="bi bi-printer me-1"></i> عرض وطباعة
                                        </a>
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

<!-- Modal: Map Biometric Device -->
<div class="modal fade" id="mapDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url("employees/{$employee['id']}/map-device") ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">ربط جهاز بصمة ZKTeco بالموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اختر جهاز البصمة</label>
                        <select name="device_id" class="form-select" required>
                            <?php foreach ($devices as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name']) ?> (<?= e($d['ip_address']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رقم المستخدم في جهاز البصمة (Device User PIN)</label>
                        <input type="text" name="device_user_id" class="form-control" placeholder="e.g. 1005" required>
                        <small class="text-muted d-block mt-1">الرقم المخزن في جهاز البصمة الفعلي لنفس الموظف</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ ومطابقة البصمة</button>
                </div>
            </form>
        </div>
    </div>
</div>
