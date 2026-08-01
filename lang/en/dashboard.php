<?php

declare(strict_types=1);

return [
    'title' => 'Dashboard',

    'stats' => [
        'users' => 'Users',
        'users_unverified' => ':count awaiting verification',
        'users_all_verified' => 'All addresses verified',
        'roles' => 'Roles',
        'roles_description' => 'Defined in Shield',
        'active_sessions' => 'Active sessions',
        'sessions_description' => 'Signed in within :minutes minutes',
        'sessions_unavailable' => 'Requires the database session driver',
    ],

    'widgets' => [
        'registrations' => [
            'heading' => 'New users',
            'description' => 'Registrations over the last :days days',
            'label' => 'Registrations',
        ],
        'recent_activity' => [
            'heading' => 'Recent activity',
            'description' => 'The latest recorded changes',
            'empty' => 'Nothing has happened yet.',
        ],
    ],
];
