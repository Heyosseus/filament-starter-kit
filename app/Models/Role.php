<?php

declare(strict_types=1);

namespace App\Models;

use Spatie\Activitylog\Models\Concerns\HasActivity;
use Spatie\Activitylog\Support\LogOptions;
use Spatie\Permission\Models\Role as SpatieRole;

/**
 * Spatie's role model, subclassed only so that changes to a role are audited
 * the same way changes to a user are. `config/permission.php` points at this
 * class, which is what makes Shield and the rest of the permission stack use it.
 */
class Role extends SpatieRole
{
    use HasActivity;

    /**
     * Who a role is and which guard it applies to. The permissions attached to
     * a role live in a pivot table rather than in an attribute, so they are not
     * captured here — the role's own fields are.
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('role')
            ->logOnly(['name', 'guard_name'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }
}
