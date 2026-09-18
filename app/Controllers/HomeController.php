<?php

namespace Controllers;

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
        $accounts = \demo_accounts();

        if (!isset($accounts[$role]) || strcasecmp($accounts[$role]['email'], $email) !== 0 || $password !== 'password') {
            $this->redirectHome('login_error=1#login');
        }

        $_SESSION['logistics_authenticated'] = true;
        $_SESSION['logistics_role'] = $role;
        $_SESSION['logistics_user_name'] = $accounts[$role]['name'];
        $_SESSION['logistics_user_email'] = $accounts[$role]['email'];

        header('Location: ' . $this->url('dashboard'));
        exit;
    }

    public function logout(): void
    {
        unset(
            $_SESSION['logistics_authenticated'],
            $_SESSION['logistics_role'],
            $_SESSION['logistics_user_name'],
            $_SESSION['logistics_user_email']
        );

        $this->redirectHome('logged_out=1');
    }

    private function redirectHome(string $query = ''): void
    {
        header('Location: ' . $this->url('home') . ($query === '' ? '' : '&' . $query));
        exit;
    }

    private function url(string $route): string
    {
        $baseUrl = \config('app.base_url', '');
        return $baseUrl . '/?route=' . urlencode($route);
    }
}
