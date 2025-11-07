<?php

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Pages\ViewUser;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertDatabaseMissing;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    actingAs(User::factory()->create());
});

it('lists users and supports searching', function () {
    $users = User::factory()->count(5)->create();

    /** @var User $firstUser */
    $firstUser = $users->first();
    /** @var User $lastUser */
    $lastUser = $users->last();

    Livewire::test(ListUsers::class)
        ->assertCanSeeTableRecords($users)
        ->searchTable($firstUser->name)
        ->assertCanSeeTableRecords($users->take(1))
        ->assertCanNotSeeTableRecords($users->skip(1))
        ->searchTable($lastUser->email)
        ->assertCanSeeTableRecords($users->take(-1))
        ->assertCanNotSeeTableRecords($users->take($users->count() - 1));
});

it('creates a user from the create page', function () {
    $email = 'filament-create@example.com';

    Livewire::test(CreateUser::class)
        ->fillForm([
            'name' => 'Filament Create',
            'email' => $email,
            'password' => 'secret-password',
        ])
        ->call('create')
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('users', [
        'name' => 'Filament Create',
        'email' => $email,
    ]);
});

it('views a user on the view page', function () {
    $user = User::factory()->create([
        'name' => 'View Me',
        'email' => 'viewme@example.com',
    ]);

    /** @var Testable $component */
    $component = Livewire::test(ViewUser::class, ['record' => $user->getKey()]);

    $component->assertSee('View Me');
    $component->assertSee('viewme@example.com');
});

it('edits a user from the edit page', function () {
    $user = User::factory()->create([
        'name' => 'Old Name',
        'email' => 'old@example.com',
    ]);

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->fillForm([
            'name' => 'New Name',
            'email' => 'new@example.com',
            'password' => 'new-secret',
        ])
        ->call('save')
        ->assertNotified();

    expect($user->refresh()->only(['name', 'email']))->toMatchArray([
        'name' => 'New Name',
        'email' => 'new@example.com',
    ]);
});

it('deletes a user via the edit page header action', function () {
    $user = User::factory()->create();

    Livewire::test(EditUser::class, ['record' => $user->getKey()])
        ->callAction('delete')
        ->assertNotified();

    assertDatabaseMissing('users', ['id' => $user->id]);
});

it('bulk deletes users from the list page', function () {
    $users = User::factory()->count(3)->create();

    Livewire::test(ListUsers::class)
        ->callTableBulkAction('delete', $users);

    foreach ($users as $user) {
        assertDatabaseMissing('users', ['id' => $user->id]);
    }
});
