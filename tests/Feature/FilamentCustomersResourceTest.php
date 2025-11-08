<?php

use App\Filament\Resources\Customers\Pages\CreateCustomer;
use App\Filament\Resources\Customers\Pages\EditCustomer;
use App\Filament\Resources\Customers\Pages\ListCustomers;
use App\Filament\Resources\Customers\Pages\ViewCustomer;
use App\Models\State;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
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
    $customers = CustomerModel::factory()->count(5)->create();

    /** @var CustomerModel $first */
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
            'phone_secondary' => '+60198765432',
            'nric' => '901201011234',
            'passport_no' => 'A12345678',
            'company_ssm_no' => 'SSM123456',
            'gst_number' => 'GST789',
            'billing_attention' => 'Finance Department',
            'credit_limit' => '10000.00',
            'risk_level' => 'Medium',
            'is_active' => true,
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
        'phone_primary' => '+60123456789',
        'phone_secondary' => '+60198765432',
        'nric' => '901201011234',
        'passport_no' => 'A12345678',
        'company_ssm_no' => 'SSM123456',
        'gst_number' => 'GST789',
        'billing_attention' => 'Finance Department',
        'credit_limit' => 10000,
        'risk_level' => 'Medium',
        'is_active' => 1,
    ]);

    $customer = CustomerModel::query()->where('email', $email)->firstOrFail();

    assertDatabaseHas('addresses', [
        'addressable_type' => 'App\Models\Customer',
        'addressable_id' => $customer->id,
        'line1' => 'Line 1',
        'is_primary' => true,
    ]);
});

it('creates a customer without an email', function () {
    $state = State::factory()->create([
        'code' => 'KUL',
        'name' => 'Kuala Lumpur',
    ]);

    Livewire::test(CreateCustomer::class)
        ->fillForm([
            'name' => 'No Email Customer',
            'customer_type' => 'Individual',
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
        'name' => 'No Email Customer',
        'email' => null,
    ]);
});

it('views a customer on the view page', function () {
    $customer = CustomerModel::factory()->create([
        'name' => 'View Customer',
        'email' => 'viewcustomer@example.com',
    ]);

    /** @var Testable $component */
    $component = Livewire::test(ViewCustomer::class, ['record' => $customer->getKey()]);

    $component->assertSee('View Customer');
    $component->assertSee('viewcustomer@example.com');
});

it('edits a customer from the edit page', function () {
    $customer = CustomerModel::factory()->create([
        'name' => 'Old Customer',
        'email' => 'oldcustomer@example.com',
    ]);

    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->fillForm([
            'name' => 'New Customer',
            'email' => 'newcustomer@example.com',
            'phone_primary' => '+60112233445',
            'phone_secondary' => '+60187654321',
            'nric' => '850505051234',
            'passport_no' => 'B98765432',
            'company_ssm_no' => 'SSM987654',
            'gst_number' => 'GST123',
            'customer_type' => 'Business',
            'billing_attention' => 'Accounts Department',
            'credit_limit' => '25000.00',
            'risk_level' => 'High',
            'is_active' => false,
        ])
        ->call('save')
        ->assertNotified();

    $customer->refresh();

    expect($customer->name)->toBe('New Customer');
    expect($customer->email)->toBe('newcustomer@example.com');
    expect($customer->phone_primary)->toBe('+60112233445');
    expect($customer->phone_secondary)->toBe('+60187654321');
    expect($customer->nric)->toBe('850505051234');
    expect($customer->passport_no)->toBe('B98765432');
    expect($customer->company_ssm_no)->toBe('SSM987654');
    expect($customer->gst_number)->toBe('GST123');

    /** @var \App\Enums\CustomerType $customerType */
    $customerType = $customer->customer_type;
    expect($customerType->value)->toBe('Business');

    expect($customer->billing_attention)->toBe('Accounts Department');
    expect($customer->credit_limit)->toBe('25000.00');

    /** @var \App\Enums\RiskLevel $riskLevel */
    $riskLevel = $customer->risk_level;
    expect($riskLevel->value)->toBe('High');
    expect($customer->is_active)->toBe(false);
});

it('preserves addresses when editing a customer without modifying addresses', function () {
    $customer = CustomerModel::factory()->create([
        'name' => 'Customer With Address',
    ]);

    // Get the initial address count
    $initialAddressCount = $customer->addresses()->count();
    expect($initialAddressCount)->toBeGreaterThan(0);

    $initialAddress = $customer->addresses()->first();

    // Edit the customer without touching addresses
    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->fillForm([
            'name' => 'Updated Customer Name',
            'phone_primary' => '+60123456789',
        ])
        ->call('save')
        ->assertNotified();

    $customer->refresh();

    // Verify customer was updated
    expect($customer->name)->toBe('Updated Customer Name');
    expect($customer->phone_primary)->toBe('+60123456789');

    // Verify addresses were preserved
    expect($customer->addresses()->count())->toBe($initialAddressCount);

    $firstAddress = $customer->addresses()->first();
    expect($firstAddress?->line1)->toBe($initialAddress?->line1);
});

it('soft deletes a customer via the edit page header action', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(EditCustomer::class, ['record' => $customer->getKey()])
        ->callAction('delete')
        ->assertNotified();

    $customer->refresh();
    assertDatabaseHas('customers', ['id' => $customer->id]);
    expect($customer->deleted_at)->not->toBeNull();
});

it('bulk soft deletes customers from the list page', function () {
    $customers = CustomerModel::factory()->count(3)->create();

    Livewire::test(ListCustomers::class)
        ->callTableBulkAction('delete', $customers);

    foreach ($customers as $cust) {
        assertDatabaseHas('customers', ['id' => $cust->id]);
        $cust->refresh();
        expect($cust->deleted_at)->not->toBeNull();
    }
});
