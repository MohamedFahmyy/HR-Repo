<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">تقييم الأداء ومؤشرات الإنجاز (Performance & KPIs)</h3>
        <p class="text-muted small mb-0">تقييم الكفاءات الدورية، مراجعات الأداء وتتبع نقاط القوة والتحسين</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addReviewModal">
            <i class="bi bi-plus-lg me-1"></i> إنشاء تقييم جديد
        </button>
    </div>
</div>

<div class="content-card mb-0">
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>الموظف</th>
                    <th>فترة التقييم</th>
                    <th>الدرجة (من 5)</th>
                    <th>ملاحظات الأداء و KPIs</th>
                    <th>المقيم</th>
                    <th>الحالة</th>
                    <th>تاريخ التقييم</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">لا توجد تقييمات أداء مسجلة حتى الآن.</td></tr>
                <?php else: ?>
                    <?php foreach ($reviews as $rev): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($rev['first_name_ar'] . ' ' . $rev['last_name_ar']) ?></div>
                                <code class="small"><?= e($rev['employee_code']) ?></code>
                            </td>
                            <td><span class="badge bg-light text-dark border"><?= e($rev['review_period']) ?></span></td>
                            <td>
                                <div class="d-flex align-items-center gap-1">
                                    <span class="fw-bold fs-6 text-primary"><?= number_format((float)$rev['rating'], 1) ?></span>
                                    <span class="text-warning">
                                        <?php for ($s = 1; $s <= 5; $s++): ?>
                                            <i class="bi bi-star<?= $s <= round((float)$rev['rating']) ? '-fill' : '' ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                            </td>
                            <td class="small" style="max-width: 250px;"><?= e($rev['kpis_feedback'] ?? '--') ?></td>
                            <td><?= e($rev['reviewer_name']) ?></td>
                            <td><span class="badge bg-success-subtle text-success">معتمد ومسجل</span></td>
                            <td><?= date('Y-m-d', strtotime($rev['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Review -->
<div class="modal fade" id="addReviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('performance') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تسجيل تقييم أداء جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الموظف المراد تقييمه</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">اختر الموظف...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= e($e['employee_code']) ?> - <?= e($e['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">فترة التقييم</label>
                        <input type="text" name="review_period" class="form-control" value="التقييم السنوي 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الدرجة المستحقة (من 1 إلى 5)</label>
                        <input type="number" step="0.1" min="1" max="5" name="rating" class="form-control" value="4.5" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">ملاحظات تحقيق الأهداف و KPIs</label>
                        <textarea name="kpis_feedback" class="form-control" rows="2" placeholder="مدى التزام الموظف بمؤشرات الأداء..."></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">نقاط القوة والإشادة</label>
                        <textarea name="strengths" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">مجالات التطوير المقترحة</label>
                        <textarea name="areas_for_improvement" class="form-control" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ التقييم</button>
                </div>
            </form>
        </div>
    </div>
</div>
