<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Request;
use App\Core\Response;
use App\Models\AuditLog;

class SettingsController extends Controller
{
    public function index(Request $request): Response
    {
        $this->authorize('settings.manage');

        $settings = Database::fetchAll("SELECT * FROM `application_settings` ORDER BY `setting_group`, `id` ASC");
        $auditLogs = (new AuditLog())->allWithUser(50);

        return $this->view('settings/index', [
            'title' => 'إعدادات النظام وسجلات التدقيق',
            'settings' => $settings,
            'auditLogs' => $auditLogs,
        ]);
    }

    public function update(Request $request): Response
    {
        $this->authorize('settings.manage');

        $inputs = $request->all();
        foreach ($inputs as $key => $value) {
            if ($key === '_csrf_token') continue;

            Database::execute(
                "UPDATE `application_settings` SET `setting_value` = :val WHERE `setting_key` = :k",
                ['val' => (string)$value, 'k' => $key]
            );
        }

        AuditLog::log('update_settings', 'ApplicationSettings', null, null, $inputs);
        $this->flash('success', 'تم حفظ وتحديث إعدادات النظام بنجاح.');

        return $this->redirect(url('settings'));
    }

    public function switchLanguage(Request $request): Response
    {
        $lang = $request->get('lang', 'ar');
        \App\Helpers\Translation::setLocale($lang);

        $referer = $_SERVER['HTTP_REFERER'] ?? url('dashboard');
        return $this->redirect($referer);
    }
}
