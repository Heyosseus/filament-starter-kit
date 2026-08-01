<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Each user page opens for someone holding the matching permission, and is
 * refused for someone who reaches the panel without it.
 */
class UserResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_super_admin_can_open_every_user_page(): void
    {
        $admin = $this->superAdmin();
        $subject = User::factory()->create();

        $this->actingAs($admin);

        $this->get('/admin/users')->assertOk();
        $this->get('/admin/users/create')->assertOk();
        $this->get("/admin/users/{$subject->getKey()}")->assertOk();
        $this->get("/admin/users/{$subject->getKey()}/edit")->assertOk();
    }

    public function test_the_index_is_refused_without_the_view_any_permission(): void
    {
        $this->actingAs($this->panelUser())
            ->get('/admin/users')
            ->assertForbidden();
    }

    public function test_the_create_page_is_refused_without_the_create_permission(): void
    {
        $this->actingAs($this->panelUser(['ViewAny:User']))
            ->get('/admin/users/create')
            ->assertForbidden();
    }

    public function test_the_edit_page_is_refused_without_the_update_permission(): void
    {
        $subject = User::factory()->create();

        $this->actingAs($this->panelUser(['ViewAny:User', 'View:User']))
            ->get("/admin/users/{$subject->getKey()}/edit")
            ->assertForbidden();
    }

    public function test_view_only_permission_opens_the_index_but_not_the_editor(): void
    {
        $subject = User::factory()->create();

        $this->actingAs($this->panelUser(['ViewAny:User', 'View:User']));

        $this->get('/admin/users')->assertOk();
        $this->get("/admin/users/{$subject->getKey()}")->assertOk();
        $this->get("/admin/users/{$subject->getKey()}/edit")->assertForbidden();
    }
}
