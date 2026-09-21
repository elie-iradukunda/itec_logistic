<?php

declare(strict_types=1);

namespace Controllers;

use Core\Flash;
use Models\AuditLog;
use Models\Settings;

/**
 * Company settings. Currency, tax rate, invoice prefix, alert windows and the
 * login lockout policy used to be constants inside the code.
 */
final class SettingsController
{
    public function index(): void
    {
        $grouped = [];
        foreach (Settings::editable() as $setting) {
            $grouped[$setting['setting_group']][] = $setting;
        }

        \view('admin/settings', [
            'title' => 'Company settings',
            'grouped' => $grouped,
            'canEdit' => \can_edit('settings'),
        ]);
    }

    public function update(): void
    {
        $submitted = $_POST['settings'] ?? [];
        if (!is_array($submitted)) {
            $submitted = [];
        }

        $allowed = array_column(Settings::editable(), 'input_type', 'setting_key');
        $changed = 0;
        $problems = [];

        foreach ($submitted as $key => $value) {
            $key = (string) $key;
            if (!isset($allowed[$key])) {
                continue;
            }

            $value = trim((string) $value);

            if ($allowed[$key] === 'number' && $value !== '' && !is_numeric($value)) {
                $problems[] = str_replace('_', ' ', $key) . ' must be a number.';
                continue;
            }

            Settings::put($key, mb_substr($value, 0, 255));
            $changed++;
        }

        if ($problems !== []) {
            Flash::error(implode(' ', $problems));
        }

        if ($changed > 0) {
            AuditLog::record('settings.updated', 'settings', null, null, ['changed' => $changed]);
            Flash::success(sprintf('%d setting(s) saved.', $changed));
        }

        header('Location: ' . \url('settings'));
        exit;
    }
}
