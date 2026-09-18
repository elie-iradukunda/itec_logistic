<?php

namespace Controllers;

use Models\LogisticsData;
use Models\AuditLog;

class LogisticsController
{
    public function vehicles(): void { $this->render('vehicles'); }
    public function trips(): void { $this->render('trips'); }
    public function drivers(): void { $this->render('drivers'); }
    public function maintenance(): void { $this->render('maintenance'); }
    public function requests(): void { $this->render('requests'); }
    public function fuel(): void { $this->render('fuel'); }
    public function expenses(): void { $this->render('expenses'); }
    public function warehouse(): void { $this->render('warehouse'); }
    public function reports(): void { $this->render('reports'); }
    public function deliveries(): void { $this->render('deliveries'); }
    public function procurement(): void { $this->render('procurement'); }
    public function users(): void { $this->render('users'); }

    private function render(string $key): void
    {
        $action = $_GET['action'] ?? 'index';
        $id = isset($_GET['id']) ? (string) $_GET['id'] : null;

        if ($key === 'reports' && $action === 'export') {
            $this->exportReport($id);
            return;
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if ($action === 'delete' && $id !== null) {
                $reason = trim((string) ($_POST['reason'] ?? ''));
                if ($reason === '') {
                    header('Location: ?route=' . urlencode($key) . '&error=delete_reason');
                    exit;
                }
                try {
                    LogisticsData::delete($key, $id, $reason);
                } catch (\Throwable) {
                    header('Location: ?route=' . urlencode($key) . '&error=delete_failed');
                    exit;
                }
            } elseif ($action === 'toggle' && $id !== null) {
                LogisticsData::setStatus($key, $id, (int) ($_POST['status'] ?? 0));
            } elseif ($action === 'create' || $action === 'edit') {
                $values = [];
                foreach (LogisticsData::module($key)['columns'] as $index => $column) {
                    $values[] = trim((string) ($_POST['field_' . $index] ?? ($_POST['existing_field_' . $index] ?? '')));
                }
                $errors = LogisticsData::validate($key, $values, $_FILES);
                if ($errors !== []) {
                    $module = LogisticsData::module($key);
                    \view('modules/index', [
                        'title' => $module['title'],
                        'moduleKey' => $key,
                        'module' => $module,
                        'action' => $action,
                        'saved' => false,
                        'record' => $values,
                        'audit' => LogisticsData::auditLog(),
                        'error' => null,
                        'errors' => $errors,
                        'reportType' => '',
                    ]);
                    return;
                }
                try {
                    LogisticsData::save($key, $id, $values, $_FILES);
                } catch (\Throwable $exception) {
                    $module = LogisticsData::module($key);
                    \view('modules/index', [
                        'title' => $module['title'],
                        'moduleKey' => $key,
                        'module' => $module,
                        'action' => $action,
                        'saved' => false,
                        'record' => $values,
                        'audit' => LogisticsData::auditLog(),
                        'error' => null,
                        'errors' => ['Unable to save record: ' . $exception->getMessage()],
                        'reportType' => '',
                    ]);
                    return;
                }
            }
            header('Location: ?route=' . urlencode($key) . '&saved=1');
            exit;
        }

        $module = LogisticsData::module($key);
        $reportType = (string) ($_GET['report_type'] ?? '');
        if ($key === 'reports' && $reportType !== '') {
            $module['rows'] = array_values(array_filter($module['rows'], static fn (array $row): bool => $row[0] === $reportType || $row[1] === $reportType));
        }
        $record = $id !== null ? LogisticsData::find($key, $id) : null;
        \view('modules/index', [
            'title' => $module['title'],
            'moduleKey' => $key,
            'module' => $module,
            'action' => $action,
            'saved' => ($_GET['saved'] ?? '') === '1',
            'record' => $record,
            'audit' => LogisticsData::auditLog(),
            'error' => $_GET['error'] ?? null,
            'reportType' => $reportType,
        ]);
    }

    private function exportReport(?string $id): void
    {
        $module = LogisticsData::module('reports');
        $rows = $module['rows'];
        $reportType = (string) ($_GET['report_type'] ?? '');
        if ($reportType !== '') {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => $row[0] === $reportType || $row[1] === $reportType));
        }
        if ($id !== null) {
            $rows = array_values(array_filter($rows, static fn (array $row): bool => (string) $row[0] === $id));
        }

        $filename = 'itec-logistics-reports-' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        AuditLog::record('report.exported', 'reports', $id, null, ['report_type' => $reportType]);
        $output = fopen('php://output', 'wb');
        fputcsv($output, $module['columns']);
        foreach ($rows as $row) {
            fputcsv($output, $row);
        }
        fclose($output);
        exit;
    }
}
