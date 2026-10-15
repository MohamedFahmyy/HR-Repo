<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">الهيكل التنظيمي (الأقسام والوظائف والفروع)</h3>
        <p class="text-muted small mb-0">إدارة البنية الإدارية للمنشأة، خطوط السلطة وتوزيع الموظفين</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addBranchModal">
            <i class="bi bi-geo-alt me-1"></i> إضافة فرع
        </button>
        <button class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addPositionModal">
            <i class="bi bi-person-badge me-1"></i> إضافة مسمى وظيفي
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeptModal">
            <i class="bi bi-diagram-3 me-1"></i> إضافة قسم جديد
        </button>
    </div>
</div>

<div class="row g-4">
    <!-- Departments List -->
    <div class="col-lg-7">
        <div class="content-card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-diagram-3-fill text-primary me-2"></i> الأقسام والإدارات</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>اسم القسم</th>
                            <th>الفرع</th>
                            <th>مدير القسم</th>
                            <th>الموظفون</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($departments as $d): ?>
                            <tr>
                                <td><code><?= e($d['code']) ?></code></td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($d['name_ar']) ?></div>
                                    <div class="text-muted small"><?= e($d['name_en']) ?></div>
                                </td>
                                <td><?= e($d['branch_name'] ?? 'الرئيسي') ?></td>
                                <td><?= e($d['manager_name'] ?: 'غير محدد') ?></td>
                                <td><span class="badge bg-primary-subtle text-primary fw-bold px-2 py-1"><?= $d['employee_count'] ?> موظف</span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Branches List -->
        <div class="content-card mb-0">
            <div class="card-header">
                <h5><i class="bi bi-geo-alt-fill text-success me-2"></i> الفروع ومواقع العمل</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>اسم الفرع</th>
                            <th>المنطقة الزمنية</th>
                            <th>العنوان والهاتف</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($branches as $b): ?>
                            <tr>
                                <td><code><?= e($b['code']) ?></code></td>
                                <td class="fw-semibold"><?= e($b['name_ar']) ?></td>
                                <td><span class="badge bg-light text-dark border"><?= e($b['timezone']) ?></span></td>
                                <td class="text-muted small"><?= e($b['address']) ?> &bull; <?= e($b['phone']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Positions List -->
    <div class="col-lg-5">
        <div class="content-card mb-0">
            <div class="card-header">
                <h5><i class="bi bi-person-badge-fill text-info me-2"></i> المسميات والوظائف</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>المسمى الوظيفي</th>
                            <th>القسم</th>
                            <th>العدد</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($positions as $p): ?>
                            <tr>
                                <td><code><?= e($p['code']) ?></code></td>
                                <td>
                                    <div class="fw-semibold"><?= e($p['title_ar']) ?></div>
                                    <div class="text-muted small"><?= e($p['title_en']) ?></div>
                                </td>
                                <td><span class="badge bg-light text-dark border"><?= e($p['department_name'] ?? '--') ?></span></td>
                                <td><span class="badge bg-light text-muted border"><?= $p['employee_count'] ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Department -->
<div class="modal fade" id="addDeptModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('departments') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">إضافة قسم جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رمز القسم (الكود)</label>
                        <input type="text" name="code" class="form-control" placeholder="DEP-XXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اسم القسم (بالعربية)</label>
                        <input type="text" name="name_ar" class="form-control" placeholder="مثال: إدارة المراجعة الداخلية" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Department Name (English)</label>
                        <input type="text" name="name_en" class="form-control" placeholder="e.g. Internal Audit" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الفرع التابع له</label>
                        <select name="branch_id" class="form-select">
                            <option value="">اختر الفرع...</option>
                            <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= e($b['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">مدير القسم</label>
                        <select name="manager_id" class="form-select">
                            <option value="">اختر المدير...</option>
                            <?php foreach ($managers as $m): ?>
                                <option value="<?= $m['id'] ?>"><?= e($m['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ القسم</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Position -->
<div class="modal fade" id="addPositionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('positions') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">إضافة مسمى وظيفي جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رمز الوظيفة (الكود)</label>
                        <input type="text" name="code" class="form-control" placeholder="POS-XXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المسمى الوظيفي (بالعربية)</label>
                        <input type="text" name="title_ar" class="form-control" placeholder="مثال: مدقق أول" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Job Title (English)</label>
                        <input type="text" name="title_en" class="form-control" placeholder="e.g. Senior Auditor" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">القسم التابع له</label>
                        <select name="department_id" class="form-select" required>
                            <?php foreach ($departments as $d): ?>
                                <option value="<?= $d['id'] ?>"><?= e($d['name_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ المسمى</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Branch -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('branches') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">إضافة فرع / موقع عمل</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رمز الفرع</label>
                        <input type="text" name="code" class="form-control" placeholder="BR-DMM" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اسم الفرع (بالعربية)</label>
                        <input type="text" name="name_ar" class="form-control" placeholder="فرع المنطقة الشرقية - الدمام" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Branch Name (English)</label>
                        <input type="text" name="name_en" class="form-control" placeholder="Eastern Branch - Dammam" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">المنطقة الزمنية</label>
                        <input type="text" name="timezone" class="form-control" value="Asia/Riyadh" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ الفرع</button>
                </div>
            </form>
        </div>
    </div>
</div>
