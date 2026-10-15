<div class="auth-header">
    <div class="brand-icon mx-auto mb-3" style="width: 50px; height: 50px; font-size: 1.8rem;">
        <i class="bi bi-buildings-fill"></i>
    </div>
    <h4 class="fw-bold mb-1"><?= e($appConfig['name']) ?></h4>
    <p class="text-white-50 small mb-0">نظام إدارة الموارد البشرية والرواتب وبصمة ZKTeco</p>
</div>

<div class="auth-body">
    <?php if ($msg = flash('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($msg = flash('info')): ?>
        <div class="alert alert-info alert-dismissible fade show small py-2" role="alert">
            <i class="bi bi-info-circle-fill me-1"></i> <?= e($msg) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form action="<?= url('login') ?>" method="POST">
        <?= csrf_field() ?>

        <div class="mb-3">
            <label class="form-label fw-semibold small text-muted"><?= __('email') ?> أو اسم المستخدم</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-person text-muted"></i></span>
                <input type="text" name="login" id="loginInput" class="form-control border-start-0" placeholder="admin@enterprise.local" required autofocus>
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-semibold small text-muted"><?= __('password') ?></label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" name="password" id="passwordInput" class="form-control border-start-0" placeholder="••••••••" required>
            </div>
        </div>

        <div class="d-grid mb-3">
            <button type="submit" class="btn btn-primary py-2 fw-semibold rounded-3 shadow-sm">
                <i class="bi bi-box-arrow-in-left me-1"></i> <?= __('login') ?>
            </button>
        </div>
    </form>

    <div class="mt-4 pt-3 border-top">
        <div class="text-muted small fw-semibold mb-2 text-center">حسابات تجريبية سريعة:</div>
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-outline-primary btn-sm flex-grow-1" onclick="document.getElementById('loginInput').value='admin@enterprise.local';document.getElementById('passwordInput').value='Admin@123456';">
                <i class="bi bi-shield-lock me-1"></i> مدير النظام
            </button>
            <button type="button" class="btn btn-outline-secondary btn-sm flex-grow-1" onclick="document.getElementById('loginInput').value='fahad.dev@enterprise.local';document.getElementById('passwordInput').value='Emp@123456';">
                <i class="bi bi-person me-1"></i> موظف (خدمة ذاتية)
            </button>
        </div>
    </div>
</div>
