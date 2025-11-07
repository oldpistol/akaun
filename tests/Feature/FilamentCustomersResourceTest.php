<?php

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\Customer;
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

it('lists customers and supports filtering', function () {
    $customers = Customer::factory()->count(5)->create();

    /** @var Customer $first */
    $first = $customers->first();

    Livewire::test(ListCustomers::class)
        ->assertCanSeeTableRecords($customers)
        ->searchTable($first->name)
        ->assertCanSeeTableRecords($customers->take(1))
        ->assertCanNotSeeTableRecords($customers->skip(1));
});

it('creates a customer from the create page', function () {
    $email = 'customer-create@example.com';
    $state = State::factory()->create([
        'code' => 'KUL',
        'name' => 'Kuala Lumpur',
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'Customer Create',
            'customer_type' => 'Individual',
            'email' => $email,
            'phone_primary' => '+60123456789',
        ])
        ->set('data.addresses', [
            'item-1' => [
                'label' => 'Primary',
                'line1' => 'Line 1',
                'city' => 'Kuala Lumpur',
                'postcode' => '50000',
                'state_id' => $state->id,
                'country_code' => 'MY',
                'is_primary' => true,
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('customers', [
        'name' => 'Customer Create',
        'email' => $email,
    ]);

    $customer = Customer::query()->where('email', $email)->firstOrFail();

    assertDatabaseHas('addresses', [
        'addressable_type' => Customer::class,
        'addressable_id' => $customer->id,
        'line1' => 'Line 1',
        'is_primary' => true,
    ]);
});

it('views a customer on the view page', function () {
    $customer = Customer::factory()->create([
        'name' => 'View Customer',
        'email' => 'viewcustomer@example.com',
    ]);

    /** @var Testable $component */
    $component = Livewire::test(ViewCustomer::class, ['record' => $customer->getKey()]);

    $component->assertSee('View Customer');
    $component->assertSee('viewcustomer@example.com');
});

it('edits a customer from the edit page', function () {
    $state = State::factory()->create([
        'code' => 'JHR',
        'name' => 'Johor',
    ]);
    $customer = Customer::factory()->create([
        'name' => 'Old Customer',
        'email' => 'oldcustomer@example.com',
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->fillForm([
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'phone_primary' => '+60112233445',
            'addresses' => [[
                'label' => 'Primary',
                'line1' => 'Line 1',
                'city' => 'Johor Bahru',
                'postcode' => '80100',
                'state_id' => $state->id,
                'country_code' => 'MY',
                'is_primary' => true,
            ]],
        ])
        ->call('save')
        ->assertNotified();

    expect($customer->refresh()->only(['name', 'email']))->toMatchArray([
        'name' => 'New Customer',
        'email' => 'newcustomer@example.com',
    ]);
});

it('soft deletes a customer via the edit page header action', function () {
    $customer = Customer::factory()->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->callAction('delete')
        ->assertNotified();

    $customer->refresh();
    assertDatabaseHas('customers', ['id' => $customer->id]);
    expect($customer->deleted_at)->not->toBeNull();
});

it('bulk soft deletes customers from the list page', function () {
    $customers = Customer::factory()->count(3)->create();

    Livewire::test(ListCustomers::class)
        ->callTableBulkAction('delete', $customers);

    foreach ($customers as $cust) {
        assertDatabaseHas('customers', ['id' => $cust->id]);
        $cust->refresh();
        expect($cust->deleted_at)->not->toBeNull();
    }
});
