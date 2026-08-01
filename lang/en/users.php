<?php

declare(strict_types=1);

return [
    'model' => [
        'singular' => 'User',
        'plural' => 'Users',
    ],

    'nav' => [
        'label' => 'Users',
    ],

    'sections' => [
        'profile' => 'Profile',
        'profile_description' => 'Who this person is and how they sign in.',
        'access' => 'Access',
        'access_description' => 'What they are allowed to do once signed in.',
    ],

    'fields' => [
        'id' => 'ID',
        'name' => 'Name',
        'email' => 'Email',
        'email_verified_at' => 'Verified at',
        'roles' => 'Roles',
        'password' => 'Password',
        'verified' => 'Verified',
        'created_at' => 'Created',
        'updated_at' => 'Updated',
    ],

    'form' => [
        'email_verified_help' => 'Leave empty to require the user to verify their address.',
        'password_help' => 'Leave blank to keep the current password.',
    ],

    'table' => [
        'email_copied' => 'Email address copied',
        'no_roles' => 'No roles',
        'verified' => 'Verified',
        'unverified' => 'Unverified',
        'empty_heading' => 'No users yet',
        'empty_description' => 'Create the first user to get started.',
    ],

    'filters' => [
        'role' => 'Role',
        'unverified' => 'Unverified only',
    ],

    'actions' => [
        'new' => 'New user',
        'import' => 'Import',
        'export' => 'Export',
    ],

    'export' => [
        'completed' => 'Your user export is ready — :count row(s) exported.',
        'failed' => ':count row(s) could not be exported.',
    ],

    'import' => [
        'completed' => 'Your user import finished — :count row(s) imported.',
        'failed' => ':count row(s) could not be imported.',
        'all_failed' => 'Your user import failed — :count row(s) could not be imported.',
    ],
];
