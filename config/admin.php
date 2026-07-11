<?php

return [
    'default_username' => env('ADMIN_DEFAULT_USERNAME', 'admin'),
    'default_password_hash' => env(
        'ADMIN_DEFAULT_PASSWORD_HASH',
        '$2y$10$B5NsivMGvRQ4rsjVqKk7CekfSSyUmq7Cxuu.B2B8K8/YI3Y7SysOS'
    ),
];
