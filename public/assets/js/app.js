/**
 * Enterprise HR & Payroll System Client Scripts
 */

document.addEventListener('DOMContentLoaded', function () {
    // 1. Mobile Sidebar Toggle
    const sidebarToggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.app-sidebar');

    if (sidebarToggleBtn && sidebar) {
        sidebarToggleBtn.addEventListener('click', function () {
            sidebar.classList.toggle('show');
        });
    }

    // 2. Global AJAX Fetch helper with CSRF protection
    window.apiFetch = async function (url, options = {}) {
        const csrfTokenMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfTokenMeta ? csrfTokenMeta.getAttribute('content') : '';

        const headers = {
            'X-Requested-With': 'XMLHttpRequest',
            'Accept': 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...(options.headers || {})
        };

        if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
            headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(options.body);
        }

        const response = await fetch(url, {
            ...options,
            headers: headers
        });

        if (!response.ok) {
            const errData = await response.json().catch(() => ({}));
            throw new Error(errData.error || errData.message || `HTTP error! status: ${response.status}`);
        }

        return await response.json();
    };

    // 3. Biometric Device Test Connection buttons
    document.querySelectorAll('.btn-test-device').forEach(button => {
        button.addEventListener('click', async function (e) {
            e.preventDefault();
            const deviceId = this.dataset.deviceId;
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري الفحص...';

            try {
                const result = await window.apiFetch(`${window.APP_URL || ''}/devices/${deviceId}/test`, {
                    method: 'POST'
                });

                if (result.success) {
                    alert('نجح الاتصال بجهاز البصمة! الحالة: متصل.');
                    window.location.reload();
                } else {
                    alert('فشل الاتصال: ' + (result.error || 'الجهاز غير متاح.'));
                }
            } catch (err) {
                alert('خطأ أثناء فحص الاتصال: ' + err.message);
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });
    });

    // 4. Biometric Device Manual Sync buttons
    document.querySelectorAll('.btn-sync-device').forEach(button => {
        button.addEventListener('click', async function (e) {
            e.preventDefault();
            const deviceId = this.dataset.deviceId;
            const originalHtml = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm"></span> جاري المزامنة...';

            try {
                const result = await window.apiFetch(`${window.APP_URL || ''}/devices/${deviceId}/sync`, {
                    method: 'POST'
                });

                if (result.success) {
                    alert(`تمت المزامنة بنجاح! السجلات المقروءة: ${result.records_fetched}، الجديدة: ${result.records_inserted}.`);
                    window.location.reload();
                } else {
                    alert('فشلت المزامنة: ' + (result.error || 'خطأ غير متوقع.'));
                }
            } catch (err) {
                alert('خطأ أثناء المزامنة: ' + err.message);
            } finally {
                this.disabled = false;
                this.innerHTML = originalHtml;
            }
        });
    });

    // 5. Auto dismiss alerts after 6 seconds
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            if (bsAlert) bsAlert.close();
        }, 6000);
    });
});
