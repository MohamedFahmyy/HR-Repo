<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">إضافة موظف جديد</h3>
        <p class="text-muted small mb-0">تسجيل بيانات الموظف الشخصية والتعاقدية، الراتب ومطابقة جهاز البصمة</p>
    </div>
    <a href="<?= url('employees') ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-right me-1"></i> العودة للقائمة
    </a>
</div>

<form action="<?= url('employees') ?>" method="POST">
    <?= csrf_field() ?>

    <div class="row g-4">
        <!-- 1. Personal & Contact Information -->
        <div class="col-lg-8">
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-person-vcard text-primary me-2"></i> البيانات الأساسية والشخصية</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">الرقم الوظيفي <span class="text-danger">*</span></label>
                            <input type="text" name="employee_code" class="form-control" value="EMP-<?= sprintf('%04d', rand(10, 9999)) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">رقم الهوية الوطنية / الإقامة <span class="text-danger">*</span></label>
                            <input type="text" name="national_id" class="form-control" placeholder="10xxxxxxxx" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">رقم جواز السفر</label>
                            <input type="text" name="passport_number" class="form-control" placeholder="Axxxxxxx">
                        </div>

                        <!-- Bilingual Names -->
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">الاسم الأول (بالعربية) <span class="text-danger">*</span></label>
                            <input type="text" name="first_name_ar" class="form-control" placeholder="مثال: عبدالله" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">اسم العائلة (بالعربية) <span class="text-danger">*</span></label>
                            <input type="text" name="last_name_ar" class="form-control" placeholder="مثال: القحطاني" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="first_name_en" class="form-control" placeholder="e.g. Abdullah" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="last_name_en" class="form-control" placeholder="e.g. Al-Qahtani" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" placeholder="user@enterprise.local" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">رقم الجوال <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" placeholder="05xxxxxxxx" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">الجنس</label>
                            <select name="gender" class="form-select">
                                <option value="male">ذكر / Male</option>
                                <option value="female">أنثى / Female</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">الجنسية</label>
                            <input type="text" name="nationality" class="form-control" value="سعودي">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">الحالة الاجتماعية</label>
                            <select name="marital_status" class="form-select">
                                <option value="single">أعزب</option>
                                <option value="married">متزوج</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2. Compensation & Salary Profile -->
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-cash-stack text-success me-2"></i> الملف المالي والراتب</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">الراتب الأساسي (Basic Salary) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="basic_salary" class="form-control" placeholder="10000.00" required>
                                <span class="input-group-text"><?= config('payroll.currency_symbol') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">بدل السكن (Housing Allowance)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="housing_allowance" class="form-control" placeholder="2500.00">
                                <span class="input-group-text"><?= config('payroll.currency_symbol') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">بدل النقل (Transport Allowance)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="transport_allowance" class="form-control" placeholder="1000.00">
                                <span class="input-group-text"><?= config('payroll.currency_symbol') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">بدلات أخرى (Other Allowances)</label>
                            <div class="input-group">
                                <input type="number" step="0.01" name="other_allowances" class="form-control" placeholder="0.00">
                                <span class="input-group-text"><?= config('payroll.currency_symbol') ?></span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">اسم البنك</label>
                            <input type="text" name="bank_name" class="form-control" placeholder="مصرف الراجحي / البنك الأهلي">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">رقم الآيبان الدولي (IBAN)</label>
                            <input type="text" name="iban" class="form-control" placeholder="SAxxxxxxxxxxxxxxxxxxxxxx">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3. Job & Organization Details -->
        <div class="col-lg-4">
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-briefcase text-primary me-2"></i> بيانات الوظيفة والتعيين</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">اختر الفرع...</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">القسم والإدارة</label>
                        <select name="department_id" class="form-select">
                            <option value="">اختر القسم...</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المسمى الوظيفي</label>
                        <select name="position_id" class="form-select">
                            <option value="">اختر المسمى...</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?= $pos['id'] ?>"><?= e($pos['title_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المدير المباشر</label>
                        <select name="manager_id" class="form-select">
                            <option value="">اختر المدير...</option>
                            <?php foreach ($managers as $mgr): ?>
                                <option value="<?= $mgr['id'] ?>"><?= e($mgr['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">نوع التوظيف</label>
                        <select name="employment_type" class="form-select">
                            <option value="full_time">دوام كامل (Full Time)</option>
                            <option value="part_time">دوام جزئي (Part Time)</option>
                            <option value="contract">عقد محدد المدة (Contract)</option>
                            <option value="probation">تحت التجربة (Probation)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">تاريخ التعيين <span class="text-danger">*</span></label>
                        <input type="date" name="hire_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">تاريخ انتهاء فترة التجربة</label>
                        <input type="date" name="probation_end_date" class="form-control" value="<?= date('Y-m-d', strtotime('+3 months')) ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الوردية المعتمدة</label>
                        <select name="shift_id" class="form-select">
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name_ar']) ?> (<?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <!-- 4. Biometric Device Mapping -->
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5><i class="bi bi-fingerprint text-info me-2"></i> ربط جهاز البصمة ZKTeco</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رقم المستخدم في جهاز البصمة (Device PIN)</label>
                        <input type="text" name="biometric_pin" class="form-control" placeholder="e.g. 1005">
                        <small class="text-muted d-block mt-1">الرقم التعريفي المخزن للموظف في جهاز ZKTeco</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">جهاز البصمة التابع له</label>
                        <select name="biometric_device_id" class="form-select">
                            <option value="1">بوابة المقر الرئيسي - ZKTeco SilkID</option>
                            <option value="2">مدخل المكاتب الإدارية - ZKTeco uFace800</option>
                            <option value="3">محاكي جهاز فرع جدة (Mock Simulator)</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-pill shadow-sm">
                    <i class="bi bi-save me-1"></i> حفظ وإنشاء ملف الموظف
                </button>
            </div>
        </div>
    </div>
</form>
