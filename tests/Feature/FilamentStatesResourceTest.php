<?php

use App\Filament\Resources\States\Pages\CreateState;
use App\Filament\Resources\States\Pages\EditState;
use App\Filament\Resources\States\Pages\ListStates;
use App\Filament\Resources\States\Pages\ViewState;
use App\Models\State;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    actingAs(User::factory()->create());
});

it('lists states and supports search', function () {
    $states = State::factory()->count(5)->create();

    /** @var State $first */
    $first = $states->first();

    Livewire::test(ListStates::class)
        ->assertCanSeeTableRecords($states)
        ->searchTable($first->name)
        ->assertCanSeeTableRecords($states->take(1))
        ->assertCanNotSeeTableRecords($states->skip(1));
});

it('creates a state from the create page', function () {
    Livewire::test(CreateState::class)
        ->fillForm([
            'code' => 'KUL',
            'name' => 'Kuala Lumpur',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('states', [
        'code' => 'KUL',
        'name' => 'Kuala Lumpur',
    ]);
});

it('views a state on the view page', function () {
    $state = State::factory()->create([
        'code' => 'JHR',
        'name' => 'Johor',
    ]);

    /** @var Testable $component */
    $component = Livewire::test(ViewState::class, ['record' => $state->getKey()]);

    $component->assertSee('JHR');
    $component->assertSee('Johor');
});

it('edits a state from the edit page', function () {
    $state = State::factory()->create([
        'code' => 'OLD',
        'name' => 'Old State',
    ]);

    Livewire::test(EditState::class, ['record' => $state->getKey()])
        ->fillForm([
            'code' => 'NEW',
            'name' => 'New State',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($state->refresh()->only(['code', 'name']))->toMatchArray([
        'code' => 'NEW',
        'name' => 'New State',
    ]);
});

it('deletes a state via the view page header action', function () {
    $state = State::factory()->create();

    Livewire::test(ViewState::class, ['record' => $state->getKey()])
        ->callAction('delete')
        ->assertNotified();

    $state->refresh();
    assertDatabaseHas('states', ['id' => $state->id]);
    expect($state->deleted_at)->not->toBeNull();
});

it('bulk deletes states from the list page', function () {
    $states = State::factory()->count(3)->create();

    Livewire::test(ListStates::class)
        ->callTableBulkAction('delete', $states);

    foreach ($states as $state) {
        assertDatabaseHas('states', ['id' => $state->id]);
        $state->refresh();
        expect($state->deleted_at)->not->toBeNull();
    }
});

it('validates unique code when creating a state', function () {
    State::factory()->create([
        'code' => 'DUP',
        'name' => 'Duplicate',
    ]);

    Livewire::test(CreateState::class)
        ->fillForm([
            'code' => 'DUP',
            'name' => 'New State',
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});

it('validates required fields when creating a state', function () {
    Livewire::test(CreateState::class)
        ->fillForm([
            'code' => '',
            'name' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'code' => 'required',
            'name' => 'required',
        ]);
});
