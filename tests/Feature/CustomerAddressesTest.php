<?php

use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Models\State;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\AddressModel;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');
    actingAs(User::factory()->create());
});

it('auto sets first address primary when none provided', function () {
    $customer = CustomerModel::factory()->create();

    // Factory already creates a primary, so remove it and add a non-primary to test auto behavior.
    $customer->addresses()->delete();
    $state = State::query()->first() ?? State::factory()->create();
    /** @var AddressModel $addr */
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
    $customer = CustomerModel::factory()->create();
    /** @var int|null $initialPrimaryId */
    $initialPrimaryId = $customer->primaryAddress?->id;
    $state = State::query()->first() ?? State::factory()->create();

    /** @var AddressModel $second */
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

    /** @var AddressModel|null $initialAddress */
    $initialAddress = AddressModel::find($initialPrimaryId);
    expect($initialAddress?->is_primary)->toBeFalse();
});

it('filters customers by primary address state', function () {
    $stateA = State::query()->first() ?? State::factory()->create(['code' => 'AAA', 'name' => 'State A']);
    $stateB = State::factory()->create(['code' => 'BBB', 'name' => 'State B']);

    $customerA = CustomerModel::factory()->create();
    $customerA->primaryAddress?->update(['state_id' => $stateA->id]);

    $customerB = CustomerModel::factory()->create();
    $customerB->primaryAddress?->update(['state_id' => $stateB->id]);

    Livewire::test(ListCustomers::class)
        ->filterTable('state', $stateA->id)
        ->assertCanSeeTableRecords([$customerA])
        ->assertCanNotSeeTableRecords([$customerB]);
});
