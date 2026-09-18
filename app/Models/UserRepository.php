<?php

declare(strict_types=1);

namespace Models;

class UserRepository extends BaseModel
{
    public function findActiveByEmail(string $email): ?array
    {
        return $this->fetchOne(
            'SELECT users.*, roles.role_key, roles.role_name
             FROM users
             INNER JOIN roles ON roles.id = users.role_id
             WHERE users.email = ? AND users.status = "active"
             LIMIT 1',
            [$email]
        );
    }

    public function touchLastLogin(int $userId): void
    {
        $statement = $this->db->prepare('UPDATE users SET last_login_at = NOW() WHERE id = ?');
        $statement->execute([$userId]);
    }
}
