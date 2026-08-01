<?php

declare(strict_types=1);

return [
    'title' => 'მთავარი',

    'stats' => [
        'users' => 'მომხმარებლები',
        'users_unverified' => ':count ელოდება დადასტურებას',
        'users_all_verified' => 'ყველა მისამართი დადასტურებულია',
        'roles' => 'როლები',
        'roles_description' => 'განსაზღვრულია Shield-ში',
        'active_sessions' => 'აქტიური სესიები',
        'sessions_description' => 'შესულია ბოლო :minutes წუთში',
        'sessions_unavailable' => 'საჭიროებს database სესიის დრაივერს',
    ],

    'widgets' => [
        'registrations' => [
            'heading' => 'ახალი მომხმარებლები',
            'description' => 'რეგისტრაციები ბოლო :days დღეში',
            'label' => 'რეგისტრაციები',
        ],
        'recent_activity' => [
            'heading' => 'ბოლო აქტივობა',
            'description' => 'ბოლოს დაფიქსირებული ცვლილებები',
            'empty' => 'ჯერ არაფერი მომხდარა.',
        ],
    ],
];
