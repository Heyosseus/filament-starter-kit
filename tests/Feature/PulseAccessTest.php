<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

/**
 * Pulse ships a gate that opens for anyone in the local environment. These
 * cover the replacement, which opens only for a held permission.
 */
class PulseAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_gate_is_closed_to_guests(): void
    {
        $this->assertFalse(Gate::forUser(null)->allows('viewPulse'));
    }

    public function test_the_gate_is_closed_without_the_permission(): void
    {
        $this->assertFalse(Gate::forUser($this->panelUser())->allows('viewPulse'));
    }

    public function test_the_gate_opens_with_the_permission(): void
    {
        $user = $this->panelUser(['view_pulse']);

        $this->assertTrue(Gate::forUser($user)->allows('viewPulse'));
    }

    public function test_the_gate_opens_for_a_super_admin(): void
    {
        $this->assertTrue(Gate::forUser($this->superAdmin())->allows('viewPulse'));
    }

    public function test_a_plain_user_is_refused_the_dashboard(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/pulse')
            ->assertForbidden();
    }

    public function test_a_permitted_user_reaches_the_dashboard(): void
    {
        $this->actingAs($this->panelUser(['view_pulse']))
            ->get('/pulse')
            ->assertOk();
    }

    /**
     * Pulse defines its own gate lazily, the first time the Gate is resolved.
     * If that ever lands after ours it would silently replace it with one that
     * opens for everyone in local and nobody anywhere else — so assert the
     * replacement is the definition actually in force.
     */
    public function test_our_gate_definition_wins_over_the_one_pulse_ships(): void
    {
        $permitted = $this->panelUser(['view_pulse']);

        $this->app['env'] = 'local';

        $this->assertTrue(Gate::forUser($permitted)->allows('viewPulse'));
        $this->assertFalse(Gate::forUser(User::factory()->create())->allows('viewPulse'));
    }
}
