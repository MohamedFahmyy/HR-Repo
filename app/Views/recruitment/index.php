<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">إدارة التوظيف والاستقطاب (Recruitment)</h3>
        <p class="text-muted small mb-0">نشر الفرص الوظيفية، استلام ومتابعة طلبات المرشحين وتحويل المقبولين إلى موظفين</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addCandidateModal">
            <i class="bi bi-person-plus me-1"></i> تسجيل مرشح
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addVacancyModal">
            <i class="bi bi-plus-lg me-1"></i> نشر فرصة وظيفية
        </button>
    </div>
</div>

<!-- Vacancies Grid -->
<div class="row g-4 mb-4">
    <div class="col-lg-5">
        <div class="content-card mb-0">
            <div class="card-header">
                <h5><i class="bi bi-megaphone text-primary me-2"></i> الوظائف الشاغرة المنشورة</h5>
            </div>
            <div class="card-body">
                <?php foreach ($vacancies as $v): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <h6 class="fw-bold mb-0 text-dark"><?= e($v['title_ar']) ?></h6>
                            <span class="badge bg-success-subtle text-success"><?= e($v['status']) ?></span>
                        </div>
                        <p class="text-muted small mb-2"><?= e($v['department_name'] ?? 'عام') ?> &bull; <?= $v['openings_count'] ?> شواغر</p>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="badge bg-light text-dark border"><i class="bi bi-people me-1"></i> <?= $v['candidates_count'] ?> مرشحين</span>
                            <small class="text-muted"><?= date('Y-m-d', strtotime($v['created_at'])) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Candidates Table -->
    <div class="col-lg-7">
        <div class="content-card mb-0">
            <div class="card-header">
                <h5><i class="bi bi-people text-info me-2"></i> سجل المتقدمين والمراحل (Candidates Pipeline)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>المرشح</th>
                            <th>الوظيفة المتقدم لها</th>
                            <th>المرحلة</th>
                            <th>الإجراء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($candidates)): ?>
                            <tr><td colspan="4" class="text-center py-4 text-muted">لا يوجد مرشحون حالياً.</td></tr>
                        <?php else: ?>
                            <?php foreach ($candidates as $c): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= e($c['first_name'] . ' ' . $c['last_name']) ?></div>
                                        <small class="text-muted d-block"><?= e($c['email']) ?> &bull; <?= e($c['phone']) ?></small>
                                    </td>
                                    <td><?= e($c['vacancy_title'] ?? 'عام') ?></td>
                                    <td>
                                        <form action="<?= url("recruitment/candidates/{$c['id']}/status") ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                                                <option value="applied" <?= $c['status'] === 'applied' ? 'selected' : '' ?>>متقدم جديد</option>
                                                <option value="shortlisted" <?= $c['status'] === 'shortlisted' ? 'selected' : '' ?>>قائمة قصيرة</option>
                                                <option value="interviewed" <?= $c['status'] === 'interviewed' ? 'selected' : '' ?>>أجرى مقابلة</option>
                                                <option value="offered" <?= $c['status'] === 'offered' ? 'selected' : '' ?>>عرض وظيفي</option>
                                                <option value="hired" <?= $c['status'] === 'hired' ? 'selected' : '' ?>>تم التوظيف</option>
                                                <option value="rejected" <?= $c['status'] === 'rejected' ? 'selected' : '' ?>>مرفوض</option>
                                            </select>
                                        </form>
                                    </td>
                                    <td>
                                        <?php if ($c['status'] === 'hired' && empty($c['converted_to_employee_id'])): ?>
                                            <form action="<?= url("recruitment/candidates/{$c['id']}/convert") ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <button type="submit" class="btn btn-success btn-sm rounded-pill px-2">
                                                    <i class="bi bi-person-check me-1"></i> تحويل لموظف
                                                </button>
                                            </form>
                                        <?php elseif (!empty($c['converted_to_employee_id'])): ?>
                                            <a href="<?= url("employees/{$c['converted_to_employee_id']}") ?>" class="btn btn-outline-primary btn-sm rounded-pill px-2">
                                                عرض الموظف
                                            </a>
                                        <?php endif; ?>
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

<!-- Modal: Add Vacancy -->
<div class="modal fade" id="addVacancyModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('recruitment/vacancies') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">نشر فرصة وظيفية جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">عنوان الوظيفة (بالعربية)</label>
                        <input type="text" name="title_ar" class="form-control" placeholder="مهندس شبكات وأمن معلومات" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Job Title (English)</label>
                        <input type="text" name="title_en" class="form-control" placeholder="Network & Security Engineer" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">القسم</label>
                            <select name="department_id" class="form-select" required>
                                <?php foreach ($departments as $d): ?>
                                    <option value="<?= $d['id'] ?>"><?= e($d['name_ar']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">عدد الشواغر</label>
                            <input type="number" name="openings_count" class="form-control" value="1" min="1" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الوصف الوظيفي والمتطلبات</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">نشر الوظيفة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Add Candidate -->
<div class="modal fade" id="addCandidateModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('recruitment/candidates') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تسجيل بيانات مرشح</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">الاسم الأول</label>
                            <input type="text" name="first_name" class="form-control" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">اسم العائلة</label>
                            <input type="text" name="last_name" class="form-control" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">البريد الإلكتروني</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رقم الجوال</label>
                        <input type="text" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الوظيفة المتقدم لها</label>
                        <select name="vacancy_id" class="form-select" required>
                            <?php foreach ($vacancies as $v): ?>
                                <option value="<?= $v['id'] ?>"><?= e($v['title_ar']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">ملاحظات أولية</label>
                        <textarea name="notes" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ المرشح</button>
                </div>
            </form>
        </div>
    </div>
</div>
