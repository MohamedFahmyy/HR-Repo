<div class="card p-5 border-0 shadow-sm mx-auto" style="max-width: 650px;">
    <div class="display-1 text-warning fw-bold mb-3"><i class="bi bi-exclamation-triangle-fill"></i></div>
    <h3 class="fw-bold mb-3">خطأ في النظام (500)</h3>
    <p class="text-muted mb-4"><?= e($message ?? 'حدث خطأ داخلي في الخادم. تم تسجيل تفاصيل الخطأ بأمان للمتابعة.') ?></p>
    <?php if (!empty($debug) && !empty($exception)): ?>
        <div class="text-start bg-light p-3 rounded mb-4" dir="ltr" style="font-size: 0.82rem; overflow-x: auto;">
            <strong><?= e(get_class($exception)) ?></strong>: <?= e($exception->getMessage()) ?><br>
            <span class="text-muted"><?= e($exception->getFile()) ?>:<?= e((string)$exception->getLine()) ?></span>
        </div>
    <?php endif; ?>
    <div>
        <a href="<?= url('dashboard') ?>" class="btn btn-primary px-4 py-2 rounded-pill">
            <i class="bi bi-house me-1"></i> العودة للرئيسية
        </a>
    </div>
</div>
