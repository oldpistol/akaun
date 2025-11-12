<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    actingAs(User::factory()->create());
});

it('lists invoices and supports filtering', function () {
    $invoices = InvoiceModel::factory()->count(5)->create();

    /** @var InvoiceModel $first */
    $first = $invoices->first();

    Livewire::test(ListInvoices::class)
        ->assertCanSeeTableRecords($invoices)
        ->searchTable($first->invoice_number)
        ->assertCanSeeTableRecords($invoices->take(1))
        ->assertCanNotSeeTableRecords($invoices->skip(1));
});

it('creates an invoice from the create page', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-202511-TEST',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            'notes' => 'Test invoice creation',
        ])
        ->set('data.items', [
            'item-1' => [
                'description' => 'Web Development',
                'quantity' => 10,
                'unit_price' => '150.00',
                'tax_rate' => '10.00',
            ],
            'item-2' => [
                'description' => 'Consulting',
                'quantity' => 5,
                'unit_price' => '200.00',
                'tax_rate' => '10.00',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('invoices', [
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-202511-TEST',
        'status' => 'Draft',
        'notes' => 'Test invoice creation',
    ]);

    $invoice = InvoiceModel::query()->where('invoice_number', 'INV-202511-TEST')->firstOrFail();

    assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'description' => 'Web Development',
        'quantity' => 10,
        'unit_price' => '150.00',
        'tax_rate' => '10.00',
    ]);

    assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'description' => 'Consulting',
        'quantity' => 5,
        'unit_price' => '200.00',
        'tax_rate' => '10.00',
    ]);
});

it('creates an invoice without items', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-202511-NOITEMS',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            'items' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('invoices', [
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-202511-NOITEMS',
    ]);
});

it('edits an invoice from the edit page', function () {
    $oldCustomer = CustomerModel::factory()->create();
    $newCustomer = CustomerModel::factory()->create();

    $invoice = InvoiceModel::factory()->create([
        'customer_id' => $oldCustomer->id,
        'invoice_number' => 'INV-202511-OLD',
        'status' => 'Draft',
        'notes' => 'Old notes',
    ]);

    Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
        ->fillForm([
            'customer_id' => $newCustomer->id,
            'invoice_number' => 'INV-202511-NEW',
            'status' => 'Sent',
            'notes' => 'Updated notes',
        ])
        ->call('save')
        ->assertNotified();

    $invoice->refresh();

    expect($invoice->customer_id)->toBe($newCustomer->id);
    expect($invoice->invoice_number)->toBe('INV-202511-NEW');
    expect($invoice->status)->toBe(InvoiceStatus::Sent);
    expect($invoice->notes)->toBe('Updated notes');
});

it('preserves invoice items when editing invoice without modifying items', function () {
    $invoice = InvoiceModel::factory()->draft()->create([
        'invoice_number' => 'INV-202511-PRESERVE',
    ]);

    // Explicitly refresh to ensure all relationships are loaded
    $invoice->refresh()->load('items');

    // Get the initial item count
    $initialItemCount = $invoice->items()->count();
    expect($initialItemCount)->toBeGreaterThan(0);

    // Store initial item data for comparison
    $initialItems = $invoice->items->map(fn ($item) => [
        'description' => $item->description,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
    ])->toArray();

    Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
        ->assertFormSet([
            'invoice_number' => 'INV-202511-PRESERVE',
        ])
        ->fillForm([
            'notes' => 'Updated invoice notes',
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    // Refresh from database
    $invoice = InvoiceModel::find($invoice->id);

    expect($invoice)->not->toBeNull();

    $invoice->load('items');

    // Verify invoice was updated
    expect($invoice->notes)->toBe('Updated invoice notes');

    // Verify items were preserved
    expect($invoice->items()->count())->toBe($initialItemCount);

    // Verify first item matches
    $currentItems = $invoice->items->map(fn ($item) => [
        'description' => $item->description,
        'quantity' => $item->quantity,
        'unit_price' => $item->unit_price,
    ])->toArray();

    expect($currentItems)->toEqual($initialItems);
});

it('updates invoice items when editing', function () {
    $invoice = InvoiceModel::factory()->draft()->create();

    Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
        ->set('data.items', [
            [
                'description' => 'Updated Product',
                'quantity' => 20,
                'unit_price' => '250.00',
                'tax_rate' => '15.00',
            ],
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    $invoice->refresh();

    assertDatabaseHas('invoice_items', [
        'invoice_id' => $invoice->id,
        'description' => 'Updated Product',
        'quantity' => 20,
        'unit_price' => '250.00',
        'tax_rate' => '15.00',
    ]);
});

it('soft deletes an invoice via the edit page header action', function () {
    $invoice = InvoiceModel::factory()->create();

    Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
        ->callAction('delete')
        ->assertNotified();

    $invoice->refresh();
    assertDatabaseHas('invoices', ['id' => $invoice->id]);
    expect($invoice->deleted_at)->not->toBeNull();
});

it('bulk soft deletes invoices from the list page', function () {
    $invoices = InvoiceModel::factory()->count(3)->create();

    Livewire::test(ListInvoices::class)
        ->callTableBulkAction('delete', $invoices);

    foreach ($invoices as $inv) {
        assertDatabaseHas('invoices', ['id' => $inv->id]);
        $inv->refresh();
        expect($inv->deleted_at)->not->toBeNull();
    }
});

it('filters invoices by status', function () {
    InvoiceModel::factory()->count(2)->draft()->create();
    InvoiceModel::factory()->count(3)->sent()->create();

    $component = Livewire::test(ListInvoices::class);

    // Filter by sent status - should see 3 records
    $component
        ->filterTable('status', 'Sent')
        ->assertCountTableRecords(3);

    // Clear filter - should see all 5 records
    $component
        ->removeTableFilter('status')
        ->assertCountTableRecords(5);
});

it('filters invoices by customer', function () {
    $customer1 = CustomerModel::factory()->create();
    $customer2 = CustomerModel::factory()->create();

    InvoiceModel::factory()->count(2)->create(['customer_id' => $customer1->id]);
    InvoiceModel::factory()->count(3)->create(['customer_id' => $customer2->id]);

    $component = Livewire::test(ListInvoices::class);

    // Filter by customer1 - should see 2 records
    $component
        ->filterTable('customer', $customer1->id)
        ->assertCountTableRecords(2);

    // Clear filter - should see all 5 records
    $component
        ->removeTableFilter('customer')
        ->assertCountTableRecords(5);
});

it('displays invoice totals in table', function () {
    $customer = CustomerModel::factory()->create();

    $invoice = InvoiceModel::factory()
        ->for($customer, 'customer')
        ->create([
            'invoice_number' => 'INV-TOTAL-TEST',
        ]);

    // Factory creates random items, so refresh to get calculated totals
    $invoice->refresh();

    Livewire::test(ListInvoices::class)
        ->assertCanSeeTableRecords([$invoice])
        ->assertSee(number_format($invoice->total, 2)); // Should see the calculated total
});

it('shows invoice status badges in table', function () {
    $draftInvoice = InvoiceModel::factory()->draft()->create();
    $sentInvoice = InvoiceModel::factory()->sent()->create();
    $paidInvoice = InvoiceModel::factory()->paid()->create();

    $component = Livewire::test(ListInvoices::class);
    $component->assertCanSeeTableRecords([$draftInvoice, $sentInvoice, $paidInvoice]);
    $component->assertSee('Draft');
    $component->assertSee('Sent');
    $component->assertSee('Paid');
});

it('validates required fields when creating invoice', function () {
    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'issued_at' => null, // Override default value
            'due_at' => null, // Override default value
        ])
        ->call('create')
        ->assertHasFormErrors(['customer_id', 'issued_at', 'due_at']);
});

it('auto-generates invoice number when left empty', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            'items' => [],
            // invoice_number intentionally left empty
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    // Verify an invoice was created with auto-generated number
    $invoice = InvoiceModel::query()->where('customer_id', $customer->id)->first();
    expect($invoice)->not->toBeNull();
    expect($invoice?->invoice_number)->toMatch('/^INV-\d{6}-\d{4}$/'); // Format: INV-YYYYMM-0001
});

it('uses provided invoice number when supplied', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_number' => 'CUSTOM-123',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            'items' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('invoices', [
        'customer_id' => $customer->id,
        'invoice_number' => 'CUSTOM-123',
    ]);
});

it('validates invoice number uniqueness', function () {
    InvoiceModel::factory()->create(['invoice_number' => 'INV-DUPLICATE']);

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => CustomerModel::factory()->create()->id,
            'invoice_number' => 'INV-DUPLICATE',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
        ])
        ->call('create')
        ->assertHasFormErrors(['invoice_number']);
});

it('calculates totals correctly when creating invoice with items', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-CALC-TEST',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
        ])
        ->set('data.items', [
            'item-1' => [
                'description' => 'Product A',
                'quantity' => 10,
                'unit_price' => '100.00',
                'tax_rate' => '10.00',
            ],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $invoice = InvoiceModel::query()->where('invoice_number', 'INV-CALC-TEST')->firstOrFail();

    // Subtotal: 10 * 100 = 1000
    // Tax: 1000 * 0.10 = 100
    // Total: 1000 + 100 = 1100
    expect($invoice->subtotal)->toBe('1000.00')
        ->and($invoice->tax_total)->toBe('100.00')
        ->and($invoice->total)->toBe('1100.00');
});

it('requires payment details when status is Paid', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-PAID-REQUIRED',
            'status' => 'Paid',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            // Intentionally omit paid_at and payment_method_id
        ])
        ->call('create')
        ->assertHasFormErrors(['paid_at', 'payment_method_id']);
});

it('does not require payment details when status is not Paid', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateInvoice::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-DRAFT-NO-PAYMENT',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            'items' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('invoices', [
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-DRAFT-NO-PAYMENT',
        'status' => 'Draft',
    ]);
});

it('allows changing invoice status from Paid to Draft without payment details', function () {
    $customer = CustomerModel::factory()->create();
    $paymentMethod = \App\Models\PaymentMethod::factory()->create();

    $invoice = InvoiceModel::factory()->paid()->create([
        'customer_id' => $customer->id,
        'invoice_number' => 'INV-PAID-TO-DRAFT',
        'payment_method_id' => $paymentMethod->id,
        'paid_at' => now(),
    ]);

    Livewire::test(EditInvoice::class, ['record' => $invoice->getKey()])
        ->fillForm([
            'status' => 'Draft',
            // When changing to Draft, payment fields should not be required
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $invoice->refresh();
    expect($invoice->status)->toBe(InvoiceStatus::Draft);
});
