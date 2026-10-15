<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h3 class="fw-bold mb-1">إدارة أجهزة البصمة ZKTeco Biometric</h3>
        <p class="text-muted small mb-0">الربط اللحظي، فحص الاتصال، المزامنة وسجلات الاستقبال المباشر (Direct TCP & ADMS PUSH)</p>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-primary btn-sm rounded-pill px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#addDeviceModal">
            <i class="bi bi-plus-lg me-1"></i> إضافة جهاز بصمة جديد
        </button>
    </div>
</div>

<!-- Devices Cards Grid -->
<div class="row g-4 mb-4">
    <?php foreach ($devices as $d): ?>
        <div class="col-lg-4 col-md-6">
            <div class="content-card h-100 mb-0 d-flex flex-column">
                <div class="card-header bg-light">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-fingerprint text-primary fs-4"></i>
                        <div>
                            <h6 class="fw-bold mb-0"><?= e($d['name']) ?></h6>
                            <small class="text-muted"><?= e($d['model_name'] ?: 'ZKTeco Terminal') ?></small>
                        </div>
                    </div>
                    <?php if ($d['last_sync_status'] === 'success'): ?>
                        <span class="badge bg-success-subtle text-success rounded-pill px-2 py-1"><i class="bi bi-check-circle me-1"></i> متصل</span>
                    <?php elseif ($d['last_sync_status'] === 'syncing'): ?>
                        <span class="badge bg-warning-subtle text-warning rounded-pill px-2 py-1"><i class="bi bi-arrow-repeat spin me-1"></i> مزامنة...</span>
                    <?php elseif ($d['last_sync_status'] === 'failed'): ?>
                        <span class="badge bg-danger-subtle text-danger rounded-pill px-2 py-1"><i class="bi bi-x-circle me-1"></i> خطأ اتصال</span>
                    <?php else: ?>
                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-2 py-1">خامل</span>
                    <?php endif; ?>
                </div>
                <div class="card-body flex-grow-1">
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">عنوان الاتصال:</span>
                        <code><?= e($d['ip_address']) ?>:<?= $d['port'] ?></code>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">البروتوكول المعتمد:</span>
                        <?php if ($d['protocol'] === 'adms_push'): ?>
                            <span class="badge bg-info-subtle text-info">ADMS PUSH (HTTP)</span>
                        <?php elseif ($d['protocol'] === 'mock_adapter'): ?>
                            <span class="badge bg-dark-subtle text-dark">Mock Simulator</span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary">Direct TCP Socket</span>
                        <?php endif; ?>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">الرقم التسلسلي (SN):</span>
                        <span class="fw-semibold small"><?= e($d['serial_number'] ?: '--') ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">الموقع والفرع:</span>
                        <span class="small"><?= e($d['branch_name'] ?? 'الرئيسي') ?></span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">المستخدمون المرتبطون:</span>
                        <span class="badge bg-light text-dark border"><?= $d['mapped_users_count'] ?> موظف</span>
                    </div>
                    <div class="mb-2 d-flex justify-content-between">
                        <span class="text-muted small">إجمالي البصمات المستلمة:</span>
                        <span class="badge bg-light text-dark border"><?= number_format((float)$d['total_punches_count']) ?> بصمة</span>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted small">آخر مزامنة ناجحة:</span>
                        <span class="small text-muted"><?= $d['last_sync_at'] ? date('H:i:s Y-m-d', strtotime($d['last_sync_at'])) : 'لم تتم بعد' ?></span>
                    </div>

                    <?php if (!empty($d['last_error_message'])): ?>
                        <div class="alert alert-danger p-2 small mt-3 mb-0">
                            <i class="bi bi-exclamation-triangle me-1"></i> <?= e($d['last_error_message']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="p-3 bg-light border-top d-flex gap-2">
                    <button class="btn btn-outline-primary btn-sm flex-grow-1 rounded-pill btn-test-device" data-device-id="<?= $d['id'] ?>">
                        <i class="bi bi-wifi me-1"></i> فحص الاتصال
                    </button>
                    <button class="btn btn-primary btn-sm flex-grow-1 rounded-pill btn-sync-device" data-device-id="<?= $d['id'] ?>">
                        <i class="bi bi-arrow-repeat me-1"></i> مزامنة الآن
                    </button>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Synchronization Logs Table -->
<div class="content-card mb-0">
    <div class="card-header">
        <h5><i class="bi bi-journal-text text-primary me-2"></i> سجلات مزامنة أجهزة البصمة (Sync Telemetry Logs)</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-custom">
            <thead>
                <tr>
                    <th>توقيت المزامنة</th>
                    <th>جهاز البصمة</th>
                    <th>نوع المزامنة</th>
                    <th>السجلات المقروءة</th>
                    <th>السجلات الجديدة</th>
                    <th>زمن الاستجابة</th>
                    <th>الحالة</th>
                    <th>التفاصيل</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentLogs)): ?>
                    <tr><td colspan="8" class="text-center py-4 text-muted">لا توجد سجلات مزامنة سابقة.</td></tr>
                <?php else: ?>
                    <?php foreach ($recentLogs as $log): ?>
                        <tr>
                            <td class="fw-semibold"><?= date('Y-m-d H:i:s', strtotime($log['created_at'])) ?></td>
                            <td><?= e($log['device_name']) ?></td>
                            <td><span class="badge bg-light text-dark border"><?= e($log['sync_type']) ?></span></td>
                            <td><?= $log['records_fetched'] ?></td>
                            <td class="fw-bold text-success">+<?= $log['records_inserted'] ?></td>
                            <td><?= $log['duration_seconds'] ?> ث</td>
                            <td>
                                <?php if ($log['status'] === 'success'): ?>
                                    <span class="badge bg-success-subtle text-success">ناجحة</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">فشلت</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= e($log['error_message'] ?? 'اكتملت بسلاسة') ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Add Device -->
<div class="modal fade" id="addDeviceModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="<?= url('devices') ?>" method="POST">
                <?= csrf_field() ?>
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">إضافة جهاز بصمة ZKTeco جديد</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">اسم الجهاز التعريفي <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="مثال: مدخل الإدارة - SilkID 1" required>
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
                    <div class="row g-2 mb-3">
                        <div class="col-8">
                            <label class="form-label small fw-semibold">عنوان IP الجهاز <span class="text-danger">*</span></label>
                            <input type="text" name="ip_address" class="form-control" placeholder="192.168.1.201" required>
                        </div>
                        <div class="col-4">
                            <label class="form-label small fw-semibold">المنفذ (Port) <span class="text-danger">*</span></label>
                            <input type="number" name="port" class="form-control" value="4370" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">بروتوكول الاتصال <span class="text-danger">*</span></label>
                        <select name="protocol" class="form-select" required>
                            <option value="direct_tcp">Direct TCP Standalone Socket (Low-level TCP)</option>
                            <option value="adms_push">ZKTeco ADMS / Cloud PUSH (HTTP/HTTPS)</option>
                            <option value="mock_adapter">محاكي اختبارات (Mock Simulation)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">كلمة سر الاتصال (Comm Key)</label>
                        <input type="text" name="comm_key" class="form-control" value="0">
                        <small class="text-muted d-block mt-1">الافتراضي في أجهزة ZKTeco هو 0</small>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small fw-semibold">الرقم التسلسلي (Serial Number)</label>
                            <input type="text" name="serial_number" class="form-control" placeholder="CJ492026...">
                        </div>
                        <div class="col-6">
                            <label class="form-label small fw-semibold">موديل الجهاز</label>
                            <input type="text" name="model_name" class="form-control" placeholder="SilkID-A400 / uFace800">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">وصف الموقع الفعلي</label>
                        <input type="text" name="location_description" class="form-control" placeholder="مثال: البوابة رقم 2 - المبنى الإداري">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary px-4">حفظ الجهاز</button>
                </div>
            </form>
        </div>
    </div>
</div>
