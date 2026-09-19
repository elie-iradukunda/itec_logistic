<?php

namespace Controllers;

class ApiController
{
    public function health(): void
    {
        $this->json(['status' => 'ok', 'app' => \config('app.name'), 'time' => date('c')]);
    }

    public function me(): void
    {
        $this->json([
            'id' => \current_user_id(),
            'name' => \current_user_name(),
            'email' => \current_user_email(),
            'role' => \current_role(),
            'role_label' => \role_label(),
            'can_switch_role' => \can_switch_role(),
        ]);
    }

    private function json(array $payload): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload);
    }
}
