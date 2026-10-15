<div class="card p-5 border-0 shadow-sm mx-auto" style="max-width: 500px;">
    <div class="display-1 text-danger fw-bold mb-3"><i class="bi bi-shield-lock-fill"></i></div>
    <h3 class="fw-bold mb-3">غير مصرح بالدخول (403)</h3>
    <p class="text-muted mb-4">ليس لديك الصلاحيات الكافية للوصول إلى هذا المورد أو تنفيذ هذا الإجراء.</p>
    <?php if (!empty($permission)): ?>
        <div class="badge bg-danger-subtle text-danger p-2 mb-4">الصلاحية المطلوبة: <?= e($permission) ?></div>
    <?php endif; ?>
    <div>
        <a href="<?= url('dashboard') ?>" class="btn btn-outline-primary px-4 py-2 rounded-pill">
            <i class="bi bi-arrow-right me-1"></i> العودة للوحة التحكم
        </a>
    </div>
</div>
