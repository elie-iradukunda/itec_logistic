<?php

namespace Controllers;

use Models\AuditLog;
use Models\UserRepository;

class HomeController
{
    public function index(): void
    {
        \view('home/index', [
            'title' => 'Logistics made visible',
            'accounts' => \demo_accounts(),
            'loginError' => ($_GET['login_error'] ?? '') === '1',
            'loginRequired' => ($_GET['login_required'] ?? '') === '1',
            'loggedOut' => ($_GET['logged_out'] ?? '') === '1',
        ]);
    }

    public function login(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirectHome();
        }

        $role = (string) ($_POST['role'] ?? '');
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $repository = new UserRepository();
        $user = $repository->findActiveByEmail($email);

        if (
            $user === null ||
            !isset(\role_definitions()[$role]) ||
            $user['role_key'] !== $role ||
            !password_verify($password, $user['password_hash'])
        ) {
            AuditLog::record('auth.login_failed', 'user', null, 'Invalid login attempt.', [
                'email' => $email,
                'role' => $role,
            ]);
            $this->redirectHome('login_error=1#login');
        }

        $repository->touchLastLogin((int) $user['id']);
        $_SESSION['logistics_authenticated'] = true;
        $_SESSION['logistics_role'] = $role;
        $_SESSION['logistics_user_id'] = (int) $user['id'];
        $_SESSION['logistics_user_name'] = $user['full_name'];
        $_SESSION['logistics_user_email'] = $user['email'];

        AuditLog::record('auth.login', 'user', (string) $user['id']);

        header('Location: ' . \url('dashboard'));
        exit;
    }

    public function logout(): void
    {
        AuditLog::record('auth.logout', 'user', \current_user_id() !== null ? (string) \current_user_id() : null);

        unset(
            $_SESSION['logistics_authenticated'],
            $_SESSION['logistics_role'],
            $_SESSION['logistics_user_id'],
            $_SESSION['logistics_user_name'],
            $_SESSION['logistics_user_email']
        );

        $this->redirectHome('logged_out=1');
    }

    private function redirectHome(string $query = ''): void
    {
        header('Location: ' . \url('') . ($query === '' ? '' : '?' . $query));
        exit;
    }
}
