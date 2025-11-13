<?php

use App\Filament\Resources\PaymentMethods\Pages\CreatePaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\EditPaymentMethod;
use App\Filament\Resources\PaymentMethods\Pages\ListPaymentMethods;
use App\Filament\Resources\PaymentMethods\Pages\ViewPaymentMethod;
use App\Models\PaymentMethod;
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

it('lists payment methods and supports search', function () {
    $paymentMethods = PaymentMethod::factory()->count(5)->create();

    /** @var PaymentMethod $first */
    $first = $paymentMethods->first();

    Livewire::test(ListPaymentMethods::class)
        ->assertCanSeeTableRecords($paymentMethods)
        ->searchTable($first->name)
        ->assertCanSeeTableRecords($paymentMethods->take(1))
        ->assertCanNotSeeTableRecords($paymentMethods->skip(1));
});

it('creates a payment method from the create page', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'name' => 'PayPal',
            'code' => 'PAYPAL',
            'description' => 'PayPal payment gateway',
            'is_active' => true,
            'sort_order' => 10,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('payment_methods', [
        'name' => 'PayPal',
        'code' => 'PAYPAL',
        'description' => 'PayPal payment gateway',
        'is_active' => true,
        'sort_order' => 10,
    ]);
});

it('views a payment method on the view page', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'name' => 'Stripe',
        'code' => 'STRIPE',
        'description' => 'Stripe payment processor',
    ]);

    /** @var Testable $component */
    $component = Livewire::test(ViewPaymentMethod::class, ['record' => $paymentMethod->getKey()]);

    $component->assertSee('Stripe');
    $component->assertSee('STRIPE');
    $component->assertSee('Stripe payment processor');
});

it('edits a payment method from the edit page', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'name' => 'Old Name',
        'code' => 'OLD',
    ]);

    Livewire::test(EditPaymentMethod::class, ['record' => $paymentMethod->getKey()])
        ->fillForm([
            'name' => 'New Name',
            'code' => 'NEW',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($paymentMethod->refresh()->only(['name', 'code']))->toMatchArray([
        'name' => 'New Name',
        'code' => 'NEW',
    ]);
});

it('deletes a payment method via the view page header action', function () {
    $paymentMethod = PaymentMethod::factory()->create();

    Livewire::test(ViewPaymentMethod::class, ['record' => $paymentMethod->getKey()])
        ->callAction('delete')
        ->assertNotified();

    assertDatabaseHas('payment_methods', ['id' => $paymentMethod->id]);
    expect($paymentMethod->refresh()->deleted_at)->not->toBeNull();
});

it('deletes a payment method via the edit page header action', function () {
    $paymentMethod = PaymentMethod::factory()->create();

    Livewire::test(EditPaymentMethod::class, ['record' => $paymentMethod->getKey()])
        ->callAction('delete')
        ->assertNotified();

    assertDatabaseHas('payment_methods', ['id' => $paymentMethod->id]);
    expect($paymentMethod->refresh()->deleted_at)->not->toBeNull();
});

it('bulk deletes payment methods from the list page', function () {
    $paymentMethods = PaymentMethod::factory()->count(3)->create();

    Livewire::test(ListPaymentMethods::class)
        ->callTableBulkAction('delete', $paymentMethods);

    foreach ($paymentMethods as $paymentMethod) {
        assertDatabaseHas('payment_methods', ['id' => $paymentMethod->id]);
        $paymentMethod->refresh();
        expect($paymentMethod->deleted_at)->not->toBeNull();
    }
});

it('validates unique code when creating a payment method', function () {
    PaymentMethod::factory()->create([
        'code' => 'DUP',
        'name' => 'Duplicate',
    ]);

    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'code' => 'DUP',
            'name' => 'New Method',
        ])
        ->call('create')
        ->assertHasFormErrors(['code' => 'unique']);
});

it('validates required fields when creating a payment method', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'name' => '',
            'code' => '',
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'required',
            'code' => 'required',
        ]);
});

it('validates unique code when editing a payment method', function () {
    $existingMethod = PaymentMethod::factory()->create(['code' => 'EXISTING']);
    $methodToEdit = PaymentMethod::factory()->create(['code' => 'EDIT']);

    Livewire::test(EditPaymentMethod::class, ['record' => $methodToEdit->getKey()])
        ->fillForm(['code' => 'EXISTING'])
        ->call('save')
        ->assertHasFormErrors(['code' => 'unique']);
});

it('allows keeping the same code when editing a payment method', function () {
    $paymentMethod = PaymentMethod::factory()->create([
        'code' => 'SAME',
        'name' => 'Same Method',
    ]);

    Livewire::test(EditPaymentMethod::class, ['record' => $paymentMethod->getKey()])
        ->fillForm([
            'code' => 'SAME',
            'name' => 'Updated Name',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($paymentMethod->refresh()->name)->toBe('Updated Name');
});

it('validates max length for name and code fields', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'name' => str_repeat('A', 101), // Exceeds 100 char limit
            'code' => str_repeat('B', 51), // Exceeds 50 char limit
        ])
        ->call('create')
        ->assertHasFormErrors([
            'name' => 'max',
            'code' => 'max',
        ]);
});

it('validates max length for description field', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'name' => 'Valid Name',
            'code' => 'VALID',
            'description' => str_repeat('A', 501), // Exceeds 500 char limit
        ])
        ->call('create')
        ->assertHasFormErrors([
            'description' => 'max',
        ]);
});

it('searches payment methods by name', function () {
    $paymentMethods = PaymentMethod::factory()->count(5)->create();

    /** @var PaymentMethod $first */
    $first = $paymentMethods->first();

    Livewire::test(ListPaymentMethods::class)
        ->assertCanSeeTableRecords($paymentMethods)
        ->searchTable($first->name)
        ->assertCanSeeTableRecords($paymentMethods->take(1))
        ->assertCanNotSeeTableRecords($paymentMethods->skip(1));
});

it('searches payment methods by code', function () {
    $paymentMethods = PaymentMethod::factory()->count(5)->create();

    /** @var PaymentMethod $first */
    $first = $paymentMethods->first();

    Livewire::test(ListPaymentMethods::class)
        ->assertCanSeeTableRecords($paymentMethods)
        ->searchTable($first->code)
        ->assertCanSeeTableRecords($paymentMethods->take(1))
        ->assertCanNotSeeTableRecords($paymentMethods->skip(1));
});

it('sorts payment methods by name in ascending order', function () {
    PaymentMethod::factory()->create(['name' => 'Zebra Payment', 'sort_order' => 1]);
    PaymentMethod::factory()->create(['name' => 'Alpha Payment', 'sort_order' => 2]);
    PaymentMethod::factory()->create(['name' => 'Middle Payment', 'sort_order' => 3]);

    Livewire::test(ListPaymentMethods::class)
        ->sortTable('name')
        ->assertCanSeeTableRecords(PaymentMethod::query()->orderBy('name')->get(), inOrder: true);
});

it('sorts payment methods by code in descending order', function () {
    PaymentMethod::factory()->create(['code' => 'AAA', 'sort_order' => 1]);
    PaymentMethod::factory()->create(['code' => 'ZZZ', 'sort_order' => 2]);
    PaymentMethod::factory()->create(['code' => 'MMM', 'sort_order' => 3]);

    Livewire::test(ListPaymentMethods::class)
        ->sortTable('code', 'desc')
        ->assertCanSeeTableRecords(PaymentMethod::query()->orderBy('code', 'desc')->get(), inOrder: true);
});

it('sorts payment methods by sort_order by default', function () {
    PaymentMethod::factory()->create(['name' => 'Third', 'sort_order' => 30]);
    PaymentMethod::factory()->create(['name' => 'First', 'sort_order' => 10]);
    PaymentMethod::factory()->create(['name' => 'Second', 'sort_order' => 20]);

    Livewire::test(ListPaymentMethods::class)
        ->assertCanSeeTableRecords(PaymentMethod::query()->orderBy('sort_order')->get(), inOrder: true);
});

it('creates payment method with default values', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'name' => 'Test Method',
            'code' => 'TEST',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    assertDatabaseHas('payment_methods', [
        'name' => 'Test Method',
        'code' => 'TEST',
        'is_active' => true,
        'sort_order' => 0,
    ]);
});

it('creates inactive payment method', function () {
    Livewire::test(CreatePaymentMethod::class)
        ->fillForm([
            'name' => 'Inactive Method',
            'code' => 'INACTIVE',
            'is_active' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    assertDatabaseHas('payment_methods', [
        'code' => 'INACTIVE',
        'is_active' => false,
    ]);
});

it('updates sort order for payment method', function () {
    $paymentMethod = PaymentMethod::factory()->create(['sort_order' => 0]);

    Livewire::test(EditPaymentMethod::class, ['record' => $paymentMethod->getKey()])
        ->fillForm(['sort_order' => 99])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($paymentMethod->refresh()->sort_order)->toBe(99);
});
