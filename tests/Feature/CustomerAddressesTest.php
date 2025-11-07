<?php

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\Address;
use App\Models\Customer;
use App\Models\State;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->create());
});

it('auto sets first address primary when none provided', function () {
    $customer = Customer::factory()->create();

    // Factory already creates a primary, so remove it and add a non-primary to test auto behavior.
    $customer->addresses()->delete();
    $state = State::query()->first() ?? State::factory()->create();
    $addr = $customer->addresses()->create([
        'line1' => 'Line 1',
        'city' => 'City',
        'postcode' => '10000',
        'state_id' => $state->id,
        'country_code' => 'MY',
        'is_primary' => false,
    ]);

    $addr->refresh();
    expect($addr->is_primary)->toBeTrue();
});

it('enforces single primary address after adding a new primary', function () {
    $customer = Customer::factory()->create();
    $initialPrimaryId = $customer->primaryAddress?->id;
    $state = State::query()->first() ?? State::factory()->create();

    $second = $customer->addresses()->create([
        'line1' => 'Second',
        'city' => 'City2',
        'postcode' => '20000',
        'state_id' => $state->id,
        'country_code' => 'MY',
        'is_primary' => true,
    ]);

    $customer->refresh();

    $primaries = $customer->addresses()->where('is_primary', true)->pluck('id');
    expect($primaries)->toHaveCount(1)->and($primaries->first())->toBe($second->id);
    expect(Address::find($initialPrimaryId)?->is_primary)->toBeFalse();
});

it('filters customers by primary address state', function () {
    $stateA = State::query()->first() ?? State::factory()->create(['code' => 'AAA', 'name' => 'State A']);
    $stateB = State::factory()->create(['code' => 'BBB', 'name' => 'State B']);

    $customerA = Customer::factory()->create();
    $customerA->primaryAddress?->update(['state_id' => $stateA->id]);

    $customerB = Customer::factory()->create();
    $customerB->primaryAddress?->update(['state_id' => $stateB->id]);

    Livewire::test(ListCustomers::class)
        ->filterTable('state', $stateA->id)
        ->assertCanSeeTableRecords([$customerA])
        ->assertCanNotSeeTableRecords([$customerB]);
});
