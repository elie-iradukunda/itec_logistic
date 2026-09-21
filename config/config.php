<?php

declare(strict_types=1);

// Secrets live in .env on the machine, not in the repository. Anything already
// in the environment wins, so a test run can still point at its own database.
(require __DIR__ . '/env.php')(dirname(__DIR__) . '/.env');

return [
    'app' => [
        'name' => 'LMS',
        'base_url' => getenv('LOGISTICS_BASE_URL') ?: '/logistics-mvc',

        // Links inside an email cannot be relative, so the public address of
        // this installation is configured rather than guessed from the request.
        'url' => rtrim((string) (getenv('APP_URL') ?: ''), '/'),
    ],
    'db' => [
        'host' => getenv('LOGISTICS_DB_HOST') ?: '127.0.0.1',
        'name' => getenv('LOGISTICS_DB_NAME') ?: 'logistics_mvc',
        'user' => getenv('LOGISTICS_DB_USER') ?: 'root',
        'pass' => getenv('LOGISTICS_DB_PASS') ?: '',
        'charset' => 'utf8mb4',
    ],
    'mail' => [
        // Resend's HTTP API. Without a key the system still records every
        // message it meant to send, so nothing is lost while mail is off.
        'api_key' => getenv('RESEND_API_KEY') ?: '',
        'from_email' => getenv('SMTP_FROM_EMAIL') ?: 'lms@updates.itec.rw',
        'from_name' => getenv('SMTP_FROM_NAME') ?: 'ITEC Ltd',
        'reply_to' => getenv('INITIAL_REPLY_TO') ?: '',
        'endpoint' => 'https://api.resend.com/emails',

        // Somewhere to send everything while testing, instead of to real people.
        // Leave blank in production.
        'redirect_all_to' => getenv('MAIL_REDIRECT_ALL_TO') ?: '',

        // A command-line run queues its messages but does not send them, so a
        // test suite or a migration never posts mail to real people. Set
        // MAIL_SEND_IN_CLI=1 to send from the console on purpose.
        'send_in_cli' => getenv('MAIL_SEND_IN_CLI') === '1',
    ],
];
