<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">الورديات وتقويم العمل والسياسات</h3>
        <p class="text-muted small mb-0">تهيئة أوقات الدوام، فترات السماح، الورديات الليلية، رمضان، والعطلات الرسمية</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#assignShiftModal">
            <i class="bi bi-person-check me-1"></i> تعيين وردية لموظف
        </button>
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addShiftModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة وردية جديدة
        </button>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Shifts List -->
    <div class="col-lg-6">
        <div class="content-card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-alarm text-primary me-2"></i> قائمة الورديات المعتمدة</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>الرمز</th>
                            <th>اسم الوردية</th>
                            <th>أوقات الدوام</th>
                            <th>الساعات</th>
                            <th>النوع</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($shifts as $s): ?>
                            <tr>
                                <td><code><?= e($s['code']) ?></code></td>
                                <td class="fw-semibold">
                                    <span class="d-inline-block rounded-circle me-1" style="width: 10px; height: 10px; background-color: <?= e($s['color']) ?>;"></span>
                                    <?= e($s['name_ar']) ?>
                                </td>
                                <td><?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?></td>
                                <td><?= number_format((float)$s['working_hours'], 1) ?> س</td>
                                <td>
                                    <?php if ($s['is_overnight']): ?>
                                        <span class="badge bg-purple-subtle text-purple border"><i class="bi bi-moon-stars"></i> ليلية (تتجاوز منتصف الليل)</span>
                                    <?php else: ?>
                                        <span class="badge bg-light text-dark border">صباحية / معتادة</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Attendance Policies -->
        <div class="content-card mb-0">
            <div class="card-header">
                <h5><i class="bi bi-shield-check text-success me-2"></i> سياسات الحضور وفترات السماح</h5>
            </div>
            <div class="card-body">
                <?php foreach ($policies as $pol): ?>
                    <div class="border rounded p-3 mb-3 bg-light">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="fw-bold mb-0 text-dark"><?= e($pol['name_ar']) ?></h6>
                            <?php if ($pol['is_default']): ?>
                                <span class="badge bg-primary">الافتراضية للمنشأة</span>
                            <?php endif; ?>
                        </div>
                        <div class="row g-2 small text-muted">
                            <div class="col-6">&bull; فترة سماح التأخير: <strong><?= $pol['grace_period_minutes'] ?> دقيقة</strong></div>
                            <div class="col-6">&bull; فترة سماح الانصراف المبكر: <strong><?= $pol['early_departure_grace_minutes'] ?> دقيقة</strong></div>
                            <div class="col-6">&bull; ساعات اليوم الكامل: <strong><?= $pol['full_day_hours'] ?> ساعات</strong></div>
                            <div class="col-6">&bull; حد العمل الإضافي: <strong><?= $pol['overtime_minimum_minutes'] ?> دقيقة</strong></div>
                            <div class="col-6">&bull; معامل الإضافي العادي: <strong><?= $pol['overtime_regular_rate'] ?>x</strong></div>
                            <div class="col-6">&bull; معامل إضافي العطلات: <strong><?= $pol['overtime_holiday_rate'] ?>x</strong></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Holidays & Calendar -->
    <div class="col-lg-6">
        <div class="content-card mb-4">
            <div class="card-header">
                <h5><i class="bi bi-calendar-check text-info me-2"></i> العطلات الرسمية المعتمدة (2026)</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>المناسبة</th>
                            <th>تاريخ البداية</th>
                            <th>تاريخ النهاية</th>
                            <th>التكرار</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($holidays as $h): ?>
                            <tr>
                                <td class="fw-semibold text-dark"><?= e($h['name_ar']) ?></td>
                                <td><?= $h['start_date'] ?></td>
                                <td><?= $h['end_date'] ?></td>
                                <td>
                                    <?= $h['is_recurring'] ? '<span class="badge bg-info-subtle text-info">سنوية</span>' : '<span class="badge bg-light text-dark">محددة</span>' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Shift Assignments -->
        <div class="content-card mb-0">
            <div class="card-header">
                <h5><i class="bi bi-people text-secondary me-2"></i> تعيينات الورديات للموظفين</h5>
            </div>
            <div class="table-responsive" style="max-height: 280px; overflow-y: auto;">
                <table class="table table-custom">
                    <thead>
                        <tr>
                            <th>الموظف</th>
                            <th>الوردية</th>
                            <th>تاريخ البدء</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($assignments as $a): ?>
                            <tr>
                                <td class="fw-semibold"><?= e($a['employee_name']) ?> <small class="text-muted">(<?= e($a['employee_code']) ?>)</small></td>
                                <td><span class="badge bg-light text-dark border"><?= e($a['shift_name']) ?></span></td>
                                <td><?= $a['start_date'] ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal: Add Shift -->
<div class="modal fade" id="addShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('shifts') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">إضافة وردية عمل جديدة</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">رمز الوردية (الكود)</label>
                        <input type="text" name="code" class="form-control" placeholder="SH-XXX" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اسم الوردية (بالعربية)</label>
                        <input type="text" name="name_ar" class="form-control" placeholder="مثال: وردية المساء الخاصة" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Shift Name (English)</label>
                        <input type="text" name="name_en" class="form-control" placeholder="e.g. Special Evening Shift" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت البدء</label>
                            <input type="time" name="start_time" class="form-control" value="08:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">وقت الانتهاء</label>
                            <input type="time" name="end_time" class="form-control" value="17:00" required>
                        </div>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">ساعات العمل المحسوبة</label>
                            <input type="number" step="0.5" name="working_hours" class="form-control" value="8.0" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">مدة الاستراحة (بالدقائق)</label>
                            <input type="number" name="break_duration_minutes" class="form-control" value="60">
                        </div>
                    </div>
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_overnight" value="1" id="overnightCheck">
                        <label class="form-check-label small fw-semibold" for="overnightCheck">
                            وردية ليلية تتجاوز منتصف الليل (Overnight Shift Crossing Midnight)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ الوردية</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Assign Shift -->
<div class="modal fade" id="assignShiftModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('shifts/assign') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">تعيين وردية عمل لموظف</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الموظف</label>
                        <select name="employee_id" class="form-select" required>
                            <option value="">اختر الموظف...</option>
                            <?php foreach ($employees as $e): ?>
                                <option value="<?= $e['id'] ?>"><?= e($e['employee_code']) ?> - <?= e($e['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">الوردية</label>
                        <select name="shift_id" class="form-select" required>
                            <?php foreach ($shifts as $s): ?>
                                <option value="<?= $s['id'] ?>"><?= e($s['name_ar']) ?> (<?= substr($s['start_time'], 0, 5) ?> - <?= substr($s['end_time'], 0, 5) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">تاريخ السريان والبدء</label>
                        <input type="date" name="start_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">تثبيت التعيين</button>
                </div>
            </form>
        </div>
    </div>
</div>
