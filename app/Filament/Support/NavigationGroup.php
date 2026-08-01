<?php

declare(strict_types=1);

namespace App\Filament\Support;

use BackedEnum;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;
use Filament\Support\Icons\Heroicon;

/**
 * Sidebar groups. Case order is the display order: who can use the application
 * comes first, then the machinery behind it.
 */
enum NavigationGroup implements HasIcon, HasLabel
{
    case UserManagement;

    case System;

    public function getLabel(): string
    {
        return match ($this) {
            self::UserManagement => __('navigation.groups.user_management'),
            self::System => __('navigation.groups.system'),
        };
    }

    public function getIcon(): BackedEnum
    {
        return match ($this) {
            self::UserManagement => Heroicon::OutlinedUsers,
            self::System => Heroicon::OutlinedCog6Tooth,
        };
    }
}
