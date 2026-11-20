<?php

return [
    // Explicitly approved existing accounts. New administrators are denied until
    // an operator reviews and adds their exact persisted identity here.
    'approved_accounts' => [
        ['id' => 1, 'username' => 'admin', 'role' => 'admin'],
        ['id' => 7, 'username' => 'admindswd', 'role' => 'admin_dswd'],
        ['id' => 8, 'username' => 'admincho', 'role' => 'admin_cho'],
    ],
    'max_bytes' => 20 * 1024 * 1024,
    'excluded_tables' => ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'],
];
