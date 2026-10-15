<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">تعديل بيانات الموظف: <?= e($employee['first_name_ar'] . ' ' . $employee['last_name_ar']) ?></h3>
        <p class="text-muted small mb-0">تحديث البيانات الشخصية والتعاقدية والمالية للموظف</p>
    </div>
    <a href="<?= url("employees/{$employee['id']}") ?>" class="btn btn-outline-secondary btn-sm rounded-pill px-3">
        <i class="bi bi-arrow-right me-1"></i> العودة للملف
    </a>
</div>

<form action="<?= url("employees/{$employee['id']}") ?>" method="POST">
    <?= csrf_field() ?>

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5>البيانات الشخصية والاتصال</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">الرقم الوظيفي <span class="text-danger">*</span></label>
                            <input type="text" name="employee_code" class="form-control" value="<?= e($employee['employee_code']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">رقم الهوية الوطنية / الإقامة <span class="text-danger">*</span></label>
                            <input type="text" name="national_id" class="form-control" value="<?= e($employee['national_id']) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">رقم جواز السفر</label>
                            <input type="text" name="passport_number" class="form-control" value="<?= e($employee['passport_number'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">الاسم الأول (بالعربية) <span class="text-danger">*</span></label>
                            <input type="text" name="first_name_ar" class="form-control" value="<?= e($employee['first_name_ar']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">اسم العائلة (بالعربية) <span class="text-danger">*</span></label>
                            <input type="text" name="last_name_ar" class="form-control" value="<?= e($employee['last_name_ar']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="first_name_en" class="form-control" value="<?= e($employee['first_name_en']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name (English) <span class="text-danger">*</span></label>
                            <input type="text" name="last_name_en" class="form-control" value="<?= e($employee['last_name_en']) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">البريد الإلكتروني <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control" value="<?= e($employee['email']) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">رقم الجوال <span class="text-danger">*</span></label>
                            <input type="text" name="phone" class="form-control" value="<?= e($employee['phone']) ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <div class="content-card mb-4">
                <div class="card-header">
                    <h5>الملف المالي والراتب</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">الراتب الأساسي <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="basic_salary" class="form-control" value="<?= $employee['basic_salary'] ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">بدل السكن</label>
                            <input type="number" step="0.01" name="housing_allowance" class="form-control" value="<?= $employee['housing_allowance'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">بدل النقل</label>
                            <input type="number" step="0.01" name="transport_allowance" class="form-control" value="<?= $employee['transport_allowance'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">بدلات أخرى</label>
                            <input type="number" step="0.01" name="other_allowances" class="form-control" value="<?= $employee['other_allowances'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">اسم البنك</label>
                            <input type="text" name="bank_name" class="form-control" value="<?= e($employee['bank_name'] ?? '') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">رقم الآيبان (IBAN)</label>
                            <input type="text" name="iban" class="form-control" value="<?= e($employee['iban'] ?? '') ?>">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="content-card mb-4">
                <div class="card-header">
                    <h5>البيانات الوظيفية</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الحالة الوظيفية</label>
                        <select name="employment_status" class="form-select">
                            <option value="active" <?= $employee['employment_status'] === 'active' ? 'selected' : '' ?>>نشط على رأس العمل</option>
                            <option value="on_leave" <?= $employee['employment_status'] === 'on_leave' ? 'selected' : '' ?>>في إجازة</option>
                            <option value="suspended" <?= $employee['employment_status'] === 'suspended' ? 'selected' : '' ?>>موقوف مؤقتاً</option>
                            <option value="terminated" <?= $employee['employment_status'] === 'terminated' ? 'selected' : '' ?>>منتهي العقد</option>
                            <option value="resigned" <?= $employee['employment_status'] === 'resigned' ? 'selected' : '' ?>>مستقيل</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الفرع</label>
                        <select name="branch_id" class="form-select">
                            <option value="">اختر الفرع...</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $employee['branch_id'] == $b['id'] ? 'selected' : '' ?>><?= e($b['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">القسم</label>
                        <select name="department_id" class="form-select">
                            <option value="">اختر القسم...</option>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>" <?= $employee['department_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المسمى الوظيفي</label>
                        <select name="position_id" class="form-select">
                            <option value="">اختر المسمى...</option>
                            <?php foreach ($positions as $pos): ?>
                                <option value="<?= $pos['id'] ?>" <?= $employee['position_id'] == $pos['id'] ? 'selected' : '' ?>><?= e($pos['title_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المدير المباشر</label>
                        <select name="manager_id" class="form-select">
                            <option value="">اختر المدير...</option>
                            <?php foreach ($managers as $mgr): ?>
                                <option value="<?= $mgr['id'] ?>" <?= $employee['manager_id'] == $mgr['id'] ? 'selected' : '' ?>><?= e($mgr['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">تاريخ التعيين <span class="text-danger">*</span></label>
                        <input type="date" name="hire_date" class="form-control" value="<?= $employee['hire_date'] ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الوردية المعتمدة</label>
                        <select name="shift_id" class="form-select">
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $currentShiftId == $s['id'] ? 'selected' : '' ?>><?= e($s['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-pill shadow-sm">
                    <i class="bi bi-save me-1"></i> حفظ التعديلات
                </button>
            </div>
        </div>
    </div>
</form>
