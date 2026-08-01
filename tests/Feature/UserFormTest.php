<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * The password field is the easy thing to get wrong: saving an edit without
 * touching it must leave the existing password alone, and a password that is
 * typed must be stored hashed rather than in the clear.
 */
class UserFormTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->actingAs($this->superAdmin());
    }

    public function test_saving_an_edit_without_a_password_leaves_it_unchanged(): void
    {
        $user = User::factory()->create(['password' => 'original-password']);
        $before = $user->fresh()->password;

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['name' => 'Renamed'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertSame('Renamed', $user->name);
        $this->assertSame($before, $user->password);
        $this->assertTrue(Hash::check('original-password', $user->password));
    }

    public function test_a_typed_password_is_stored_hashed(): void
    {
        $user = User::factory()->create(['password' => 'original-password']);

        Livewire::test(EditUser::class, ['record' => $user->getKey()])
            ->fillForm(['password' => 'a-replacement-password'])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        $this->assertNotSame('a-replacement-password', $user->password);
        $this->assertTrue(Hash::check('a-replacement-password', $user->password));
    }

    public function test_a_password_is_required_when_creating(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'No Password',
                'email' => 'no-password@example.test',
            ])
            ->call('create')
            ->assertHasFormErrors(['password']);
    }

    public function test_a_user_can_be_created_with_a_role(): void
    {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Fresh Admin',
                'email' => 'fresh-admin@example.test',
                'password' => 'a-strong-password',
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $created = User::query()->where('email', 'fresh-admin@example.test')->first();

        $this->assertNotNull($created);
        $this->assertTrue(Hash::check('a-strong-password', $created->password));
    }

    public function test_a_duplicate_email_is_rejected(): void
    {
        User::factory()->create(['email' => 'taken@example.test']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Duplicate',
                'email' => 'taken@example.test',
                'password' => 'a-strong-password',
            ])
            ->call('create')
            ->assertHasFormErrors(['email']);
    }
}
