<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Role;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

abstract class TestCase extends BaseTestCase
{
    /**
     * Every permission the panel's own resources define. Kept here rather than
     * generated via shield:generate so the tests state their expectations
     * explicitly and do not depend on the generator's output.
     *
     * @var list<string>
     */
    protected const array USER_PERMISSIONS = [
        'ViewAny:User',
        'View:User',
        'Create:User',
        'Update:User',
        'Delete:User',
    ];

    /**
     * A user who may reach the panel and do everything in it.
     */
    protected function superAdmin(): User
    {
        $role = $this->role(Utils::getSuperAdminName());

        $role->syncPermissions($this->permissions([
            ...self::USER_PERMISSIONS,
            'ViewAny:Activity',
            'View:Activity',
            'view_pulse',
        ]));

        return tap(User::factory()->create())->assignRole($role);
    }

    /**
     * A user who may reach the panel but holds only the permissions given.
     *
     * @param  list<string>  $permissions
     */
    protected function panelUser(array $permissions = []): User
    {
        $role = $this->role(Utils::getPanelUserRoleName());
        $role->syncPermissions($this->permissions($permissions));

        return tap(User::factory()->create())->assignRole($role);
    }

    private function role(string $name): Role
    {
        return Role::findOrCreate($name, Utils::getFilamentAuthGuard());
    }

    /**
     * @param  list<string>  $names
     * @return list<Permission>
     */
    private function permissions(array $names): array
    {
        $guard = Utils::getFilamentAuthGuard();

        $permissions = array_map(
            fn (string $name): Permission => Permission::findOrCreate($name, $guard),
            $names,
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $permissions;
    }
}
