<?php

declare(strict_types=1);

namespace Controllers;

use Models\AuditLog;
use Models\Schema;

/**
 * Serves proof-of-delivery files, signatures, receipts and scanned documents.
 *
 * Uploads live outside the web root, so before this route existed a file could
 * be uploaded but never opened again. Access is checked against the permission
 * of the module the file belongs to.
 */
final class FileController
{
    private const MIME = [
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'webp' => 'image/webp',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    ];

    public function show(array $params): void
    {
        $module = (string) ($params['module'] ?? '');
        $name = (string) ($params['name'] ?? '');

        // The module segment must be a real module and the file name must be a
        // plain name, so no request can walk out of the uploads folder.
        if (!Schema::has($module) || preg_match('/^[A-Za-z0-9._-]+$/', $name) !== 1 || str_contains($name, '..')) {
            $this->fail(404, 'File not found.');
        }

        if (!\role_can($module, 'view')) {
            $this->fail(403, 'This file is not available for your role.');
        }

        $root = realpath((string) \config('uploads.path', dirname(__DIR__, 2) . '/storage/uploads'));
        $path = realpath($root . '/' . $module . '/' . $name);

        if ($root === false || $path === false || !str_starts_with($path, $root . DIRECTORY_SEPARATOR) || !is_file($path)) {
            $this->fail(404, 'File not found.');
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $type = self::MIME[$extension] ?? 'application/octet-stream';
        $inline = in_array($extension, ['pdf', 'png', 'jpg', 'jpeg', 'webp'], true);

        AuditLog::record('file.opened', $module, $name);

        header('Content-Type: ' . $type);
        header('Content-Length: ' . filesize($path));
        header('Content-Disposition: ' . ($inline ? 'inline' : 'attachment') . '; filename="' . basename($path) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=600');
        readfile($path);
        exit;
    }

    private function fail(int $status, string $message): never
    {
        http_response_code($status);
        \view('layouts/error', ['title' => $status === 403 ? 'Not allowed' : 'File not found', 'message' => $message]);
        exit;
    }
}
