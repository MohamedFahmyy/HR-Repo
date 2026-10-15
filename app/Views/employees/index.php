<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">دليل الموظفين والسجلات</h3>
        <p class="text-muted small mb-0">البحث والتصفية وإدارة بيانات الموظفين، عقود العمل وربط أجهزة البصمة</p>
    </div>
    <a href="<?= url('employees/create') ?>" class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm">
        <i class="bi bi-person-plus-fill me-1"></i> إضافة موظف جديد
    </a>
</div>

<!-- Filters Card -->
<div class="content-card mb-4">
    <div class="card-body">
        <form action="<?= url('employees') ?>" method="GET" class="row g-3">
            <div class="col-md-4">
                <label class="form-label small fw-semibold text-muted">البحث بالاسم / الرقم الوظيفي / الهوية / البريد</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-light"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="search" class="form-control" placeholder="ابحث هنا..." value="<?= e($filters['search'] ?? '') ?>">
                </div>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">القسم</label>
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">جميع الأقسام</option>
                    <?php foreach ($departments as $dept): ?>
                        <option value="<?= $dept['id'] ?>" <?= ($filters['department_id'] ?? '') == $dept['id'] ? 'selected' : '' ?>>
                            <?= e($dept['name_ar']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label small fw-semibold text-muted">حالة العمل</label>
                <select name="status" class="form-select form-select-sm">
                    <option value="">جميع الحالات</option>
                    <option value="active" <?= ($filters['employment_status'] ?? '') === 'active' ? 'selected' : '' ?>>نشط على رأس العمل</option>
                    <option value="on_leave" <?= ($filters['employment_status'] ?? '') === 'on_leave' ? 'selected' : '' ?>>في إجازة</option>
                    <option value="suspended" <?= ($filters['employment_status'] ?? '') === 'suspended' ? 'selected' : '' ?>>موقوف</option>
                    <option value="resigned" <?= ($filters['employment_status'] ?? '') === 'resigned' ? 'selected' : '' ?>>مستقيل</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">
                    <i class="bi bi-funnel me-1"></i> تصفية
                </button>
                <a href="<?= url('employees') ?>" class="btn btn-outline-secondary btn-sm">إعادة ضبط</a>
            </div>
        </form>
    </div>
</div>

<!-- Employees Table Card -->
<div class="content-card">
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الرقم الوظيفي</th>
                    <th>الموظف</th>
                    <th>القسم والمسمى الوظيفي</th>
                    <th>الفرع</th>
                    <th>تاريخ التعيين</th>
                    <th>الراتب الأساسي</th>
                    <th>الحالة</th>
                    <th>الإجراءات</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($employees)): ?>
                    <tr>
                        <td colspan="8" class="text-center py-5 text-muted">لا يوجد موظفون يطابقون معايير البحث.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($employees as $emp): ?>
                        <tr>
                            <td><code><?= e($emp['employee_code']) ?></code></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="rounded-circle bg-light border text-primary fw-bold d-flex align-items-center justify-content-center" style="width: 36px; height: 36px;">
                                        <?= mb_substr($emp['first_name_ar'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="fw-semibold text-dark"><?= e($emp['first_name_ar'] . ' ' . $emp['last_name_ar']) ?></div>
                                        <div class="text-muted small"><?= e($emp['first_name_en'] . ' ' . $emp['last_name_en']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium text-dark"><?= e($emp['department_name'] ?? 'غير محدد') ?></div>
                                <div class="text-muted small"><?= e($emp['position_title'] ?? 'غير محدد') ?></div>
                            </td>
                            <td><?= e($emp['branch_name'] ?? 'الرئيسي') ?></td>
                            <td><?= date('Y-m-d', strtotime($emp['hire_date'])) ?></td>
                            <td class="fw-semibold text-success">
                                <?= number_format((float)$emp['basic_salary'], 2) ?> <?= config('payroll.currency_symbol') ?>
                            </td>
                            <td>
                                <?php if ($emp['employment_status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success">نشط</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary"><?= e($emp['employment_status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="<?= url("employees/{$emp['id']}") ?>" class="btn btn-outline-primary" title="عرض الملف">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                    <a href="<?= url("employees/{$emp['id']}/edit") ?>" class="btn btn-outline-secondary" title="تعديل">
                                        <i class="bi bi-pencil"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pagination['last_page'] > 1): ?>
        <div class="p-3 border-top d-flex justify-content-between align-items-center">
            <span class="text-muted small">عرض <?= count($employees) ?> من إجمالي <?= $pagination['total'] ?> سجل</span>
            <ul class="pagination pagination-sm m-0">
                <?php for ($p = 1; $p <= $pagination['last_page']; $p++): ?>
                    <li class="page-item <?= $pagination['current_page'] === $p ? 'active' : '' ?>">
                        <a class="page-link" href="<?= url("employees?page={$p}&search=" . urlencode($filters['search'] ?? '') . "&department_id=" . ($filters['department_id'] ?? '')) ?>"><?= $p ?></a>
                    </li>
                <?php endfor; ?>
            </ul>
        </div>
    <?php endif; ?>
</div>
