<div class="payslip-document">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center border-bottom pb-4 mb-4">
        <div>
            <h4 class="fw-bold mb-1 text-primary"><?= e(config('app.name')) ?></h4>
            <div class="text-muted small">قسيمة تفصيل الراتب الشهري | Salary Payslip</div>
            <div class="text-muted small">الرقم الضريبي: 310987654300003 &bull; س.ت: 1010998877</div>
        </div>
        <div class="text-end">
            <span class="badge bg-light text-dark border fs-6 px-3 py-2"><code><?= e($item['payslip_number']) ?></code></span>
            <div class="text-muted small mt-1">تاريخ الإصدار: <?= $item['issue_date'] ?></div>
        </div>
    </div>

    <!-- Employee Metadata Grid -->
    <div class="bg-light p-3 rounded-3 mb-4 border">
        <div class="row g-2 small">
            <div class="col-6 col-md-3 text-muted">اسم الموظف:</div>
            <div class="col-6 col-md-3 fw-bold text-dark"><?= e($item['first_name_ar'] . ' ' . $item['last_name_ar']) ?></div>

            <div class="col-6 col-md-3 text-muted">الرقم الوظيفي:</div>
            <div class="col-6 col-md-3 fw-bold"><code><?= e($item['employee_code']) ?></code></div>

            <div class="col-6 col-md-3 text-muted">Employee Name:</div>
            <div class="col-6 col-md-3 fw-bold text-dark"><?= e($item['first_name_en'] . ' ' . $item['last_name_en']) ?></div>

            <div class="col-6 col-md-3 text-muted">رقم الهوية / الإقامة:</div>
            <div class="col-6 col-md-3 fw-bold"><?= e($item['national_id']) ?></div>

            <div class="col-6 col-md-3 text-muted">القسم والوظيفة:</div>
            <div class="col-6 col-md-3"><?= e($item['department_name']) ?> - <?= e($item['position_title']) ?></div>

            <div class="col-6 col-md-3 text-muted">فترة المسير:</div>
            <div class="col-6 col-md-3 fw-bold text-primary"><?= e($item['period_name_ar']) ?></div>

            <div class="col-6 col-md-3 text-muted">الحساب البنكي (IBAN):</div>
            <div class="col-6 col-md-9"><code><?= e($item['iban']) ?></code> (<?= e($item['bank_name'] ?? 'البنك') ?>)</div>
        </div>
    </div>

    <!-- Earnings & Deductions Tables -->
    <div class="row g-4 mb-4">
        <!-- Earnings -->
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100">
                <h6 class="fw-bold text-success border-bottom pb-2 mb-3">
                    <i class="bi bi-plus-circle me-1"></i> الاستحقاقات والبدلات (Earnings)
                </h6>
                <table class="table table-borderless table-sm small m-0">
                    <tr><td>الراتب الأساسي (Basic Salary)</td><td class="text-end fw-semibold"><?= number_format((float)$item['basic_salary'], 2) ?></td></tr>
                    <tr><td>بدل سكن (Housing Allowance)</td><td class="text-end"><?= number_format((float)$item['housing_allowance'], 2) ?></td></tr>
                    <tr><td>بدل نقل (Transport Allowance)</td><td class="text-end"><?= number_format((float)$item['transport_allowance'], 2) ?></td></tr>
                    <?php if ((float)$item['other_allowances'] > 0): ?>
                        <tr><td>بدلات أخرى (Other Allowances)</td><td class="text-end"><?= number_format((float)$item['other_allowances'], 2) ?></td></tr>
                    <?php endif; ?>
                    <?php if ((float)$item['overtime_pay'] > 0): ?>
                        <tr class="text-primary"><td>عمل إضافي معتمد (<?= $item['overtime_hours'] ?> ساعة)</td><td class="text-end fw-bold"><?= number_format((float)$item['overtime_pay'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr class="border-top fw-bold text-success">
                        <td class="pt-2">إجمالي الاستحقاقات (Gross Pay)</td>
                        <td class="text-end pt-2"><?= number_format((float)$item['gross_salary'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Deductions -->
        <div class="col-md-6">
            <div class="border rounded-3 p-3 h-100">
                <h6 class="fw-bold text-danger border-bottom pb-2 mb-3">
                    <i class="bi bi-dash-circle me-1"></i> الاستقطاعات والخصم (Deductions)
                </h6>
                <table class="table table-borderless table-sm small m-0">
                    <tr>
                        <td>خصم أيام غياب (<?= (float)$item['absence_days'] ?> يوم)</td>
                        <td class="text-end text-danger"><?= number_format((float)$item['absence_deduction'], 2) ?></td>
                    </tr>
                    <tr>
                        <td>خصم تأخيرات دوام (<?= (int)$item['late_minutes'] ?> دقيقة)</td>
                        <td class="text-end text-danger"><?= number_format((float)$item['late_deduction'], 2) ?></td>
                    </tr>
                    <?php if ((float)$item['unpaid_leave_deduction'] > 0): ?>
                        <tr><td>إجازة غير مدفوعة الراتب</td><td class="text-end text-danger"><?= number_format((float)$item['unpaid_leave_deduction'], 2) ?></td></tr>
                    <?php endif; ?>
                    <tr class="border-top fw-bold text-danger">
                        <td class="pt-2">إجمالي الاستقطاعات (Total Deductions)</td>
                        <td class="text-end pt-2"><?= number_format((float)$item['total_deductions'], 2) ?> <?= config('payroll.currency_symbol') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Net Pay Box -->
    <div class="bg-primary-subtle text-primary border border-primary-subtle p-3 rounded-3 d-flex justify-content-between align-items-center mb-5">
        <div>
            <h5 class="fw-bold mb-0">صافي المبلغ المستحق للتحويل (Net Pay)</h5>
            <small class="text-muted">تم اعتماده وإدراجه في ملف الصرف البنكي</small>
        </div>
        <div class="display-6 fw-bold text-primary">
            <?= number_format((float)$item['net_salary'], 2) ?> <?= config('payroll.currency_symbol') ?>
        </div>
    </div>

    <!-- Signatures Section -->
    <div class="row pt-4 text-center small text-muted border-top">
        <div class="col-4">
            <p class="fw-semibold mb-4">إعداد قسم الرواتب</p>
            <p>_______________________</p>
        </div>
        <div class="col-4">
            <p class="fw-semibold mb-4">اعتماد الموارد البشرية</p>
            <p>_______________________</p>
        </div>
        <div class="col-4">
            <p class="fw-semibold mb-4">توقيع الموظف بالاستلام</p>
            <p>_______________________</p>
        </div>
    </div>
</div>
