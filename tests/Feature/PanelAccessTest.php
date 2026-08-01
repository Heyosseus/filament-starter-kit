<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The panel boots, the front door works, and a user who has no business in the
 * panel cannot get in even with valid credentials.
 */
class PanelAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_root_url_redirects_to_the_panel(): void
    {
        $this->get('/')->assertRedirect('/admin');
    }

    public function test_the_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk();
    }

    public function test_guests_are_sent_to_the_login_page(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
    }

    public function test_a_user_with_no_panel_role_is_refused(): void
    {
        $stranger = User::factory()->create();

        $this->actingAs($stranger)
            ->get('/admin')
            ->assertForbidden();
    }

    public function test_a_panel_user_reaches_the_dashboard(): void
    {
        $this->actingAs($this->panelUser())
            ->get('/admin')
            ->assertOk();
    }
}
