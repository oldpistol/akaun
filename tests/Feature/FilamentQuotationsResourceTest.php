<?php

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\Pages\CreateQuotation;
use App\Filament\Resources\Quotations\Pages\EditQuotation;
use App\Filament\Resources\Quotations\Pages\ListQuotations;
use App\Filament\Resources\Quotations\Pages\ViewQuotation;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel('admin');

    actingAs(User::factory()->create());
});

it('lists quotations and supports filtering', function () {
    $quotations = QuotationModel::factory()->count(5)->create();

    /** @var QuotationModel $first */
    $first = $quotations->first();

    Livewire::test(ListQuotations::class)
        ->assertCanSeeTableRecords($quotations)
        ->searchTable($first->quotation_number)
        ->assertCanSeeTableRecords($quotations->take(1))
        ->assertCanNotSeeTableRecords($quotations->skip(1));
});

it('creates a quotation from the create page', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-202511-TEST',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'valid_until' => '2025-12-01',
            'discount_rate' => '10.00',
            'notes' => 'Test quotation creation',
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

    assertDatabaseHas('quotations', [
        'customer_id' => $customer->id,
        'quotation_number' => 'QUO-202511-TEST',
        'status' => 'Draft',
        'discount_rate' => '10.00',
        'notes' => 'Test quotation creation',
    ]);

    $quotation = QuotationModel::query()->where('quotation_number', 'QUO-202511-TEST')->firstOrFail();

    assertDatabaseHas('quotation_items', [
        'quotation_id' => $quotation->id,
        'description' => 'Web Development',
        'quantity' => 10,
        'unit_price' => '150.00',
        'tax_rate' => '10.00',
    ]);

    assertDatabaseHas('quotation_items', [
        'quotation_id' => $quotation->id,
        'description' => 'Consulting',
        'quantity' => 5,
        'unit_price' => '200.00',
        'tax_rate' => '10.00',
    ]);
});

it('creates a quotation without items', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-202511-NOITEMS',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'valid_until' => '2025-12-01',
            'items' => [],
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    assertDatabaseHas('quotations', [
        'customer_id' => $customer->id,
        'quotation_number' => 'QUO-202511-NOITEMS',
    ]);
});

it('edits a quotation from the edit page', function () {
    $oldCustomer = CustomerModel::factory()->create();
    $newCustomer = CustomerModel::factory()->create();

    $quotation = QuotationModel::factory()->create([
        'customer_id' => $oldCustomer->id,
        'quotation_number' => 'QUO-202511-OLD',
        'status' => 'Draft',
        'notes' => 'Old notes',
    ]);

    Livewire::test(EditQuotation::class, ['record' => $quotation->getKey()])
        ->fillForm([
            'customer_id' => $newCustomer->id,
            'quotation_number' => 'QUO-202511-NEW',
            'status' => 'Sent',
            'notes' => 'Updated notes',
        ])
        ->call('save')
        ->assertNotified();

    $quotation->refresh();

    expect($quotation->customer_id)->toBe($newCustomer->id);
    expect($quotation->quotation_number)->toBe('QUO-202511-NEW');
    expect($quotation->status)->toBe(QuotationStatus::Sent);
    expect($quotation->notes)->toBe('Updated notes');
});

it('auto-generates quotation number when left empty', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'valid_until' => '2025-12-01',
            'items' => [],
            // quotation_number intentionally left empty
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified()
        ->assertRedirect();

    // Verify a quotation was created with auto-generated number
    $quotation = QuotationModel::query()->where('customer_id', $customer->id)->first();
    expect($quotation)->not->toBeNull();
    expect($quotation?->quotation_number)->toMatch('/^QUO-\d{6}-\d{4}$/'); // Format: QUO-YYYYMM-0001
});

it('calculates totals correctly with discount when creating quotation', function () {
    $customer = CustomerModel::factory()->create();

    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-CALC-TEST',
            'status' => 'Draft',
            'issued_at' => '2025-11-01',
            'valid_until' => '2025-12-01',
            'discount_rate' => '10.00',
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

    $quotation = QuotationModel::query()->where('quotation_number', 'QUO-CALC-TEST')->firstOrFail();

    // Subtotal: 10 * 100 = 1000
    // Tax: 1000 * 0.10 = 100
    // Discount: 1000 * 0.10 = 100
    // Total: 1000 + 100 - 100 = 1000
    expect($quotation->subtotal)->toBe('1000.00')
        ->and($quotation->tax_total)->toBe('100.00')
        ->and($quotation->discount_amount)->toBe('100.00')
        ->and($quotation->total)->toBe('1000.00');
});

it('filters quotations by status', function () {
    QuotationModel::factory()->count(2)->draft()->create();
    QuotationModel::factory()->count(3)->sent()->create();

    $component = Livewire::test(ListQuotations::class);

    // Filter by sent status - should see 3 records
    $component
        ->filterTable('status', 'Sent')
        ->assertCountTableRecords(3);

    // Clear filter - should see all 5 records
    $component
        ->removeTableFilter('status')
        ->assertCountTableRecords(5);
});

it('filters quotations by customer', function () {
    $customer1 = CustomerModel::factory()->create();
    $customer2 = CustomerModel::factory()->create();

    QuotationModel::factory()->count(2)->create(['customer_id' => $customer1->id]);
    QuotationModel::factory()->count(3)->create(['customer_id' => $customer2->id]);

    $component = Livewire::test(ListQuotations::class);

    // Filter by customer1 - should see 2 records
    $component
        ->filterTable('customer', $customer1->id)
        ->assertCountTableRecords(2);

    // Clear filter - should see all 5 records
    $component
        ->removeTableFilter('customer')
        ->assertCountTableRecords(5);
});

it('shows quotation status badges in table', function () {
    $draftQuotation = QuotationModel::factory()->draft()->create();
    $sentQuotation = QuotationModel::factory()->sent()->create();
    $acceptedQuotation = QuotationModel::factory()->accepted()->create();

    $component = Livewire::test(ListQuotations::class);
    $component->assertCanSeeTableRecords([$draftQuotation, $sentQuotation, $acceptedQuotation]);
    $component->assertSee('Draft');
    $component->assertSee('Sent');
    $component->assertSee('Accepted');
});

it('validates required fields when creating quotation', function () {
    Livewire::test(CreateQuotation::class)
        ->fillForm([
            'issued_at' => null,
            'valid_until' => null,
        ])
        ->call('create')
        ->assertHasFormErrors(['customer_id', 'issued_at', 'valid_until']);
});

it('soft deletes a quotation via the edit page header action', function () {
    $quotation = QuotationModel::factory()->create();

    Livewire::test(EditQuotation::class, ['record' => $quotation->getKey()])
        ->callAction('delete')
        ->assertNotified();

    assertSoftDeleted('quotations', ['id' => $quotation->id]);
});

it('converts an accepted quotation to an invoice', function () {
    $quotation = QuotationModel::factory()->accepted()->create();

    Livewire::test(ListQuotations::class)
        ->callTableAction('convert_to_invoice', $quotation)
        ->assertNotified();

    $quotation->refresh();

    // Verify quotation is marked as converted
    expect($quotation->status)->toBe(QuotationStatus::Converted);
    expect($quotation->converted_at)->not->toBeNull();
    expect($quotation->converted_invoice_id)->not->toBeNull();

    // Verify invoice was created
    assertDatabaseHas('invoices', [
        'id' => $quotation->converted_invoice_id,
        'customer_id' => $quotation->customer_id,
    ]);
});

it('views a quotation on the view page', function () {
    $quotation = QuotationModel::factory()->create([
        'quotation_number' => 'QUO-VIEW-TEST',
    ]);

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->assertSee('QUO-VIEW-TEST')
        ->assertSee($quotation->customer->name);
});

it('views quotation with items on the view page', function () {
    $quotation = QuotationModel::factory()->create([
        'quotation_number' => 'QUO-ITEMS-VIEW',
    ]);

    $quotation->items()->create([
        'description' => 'Consulting Service',
        'quantity' => 3,
        'unit_price' => '500.00',
        'tax_rate' => '10.00',
    ]);

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->assertSee('Consulting Service')
        ->assertSee('3')
        ->assertSee('500.00');
});

it('views quotation with discount information', function () {
    $quotation = QuotationModel::factory()->create([
        'quotation_number' => 'QUO-DISCOUNT-VIEW',
        'discount_rate' => '15.00',
    ]);

    $quotation->recalculateTotals();
    $quotation->refresh();

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->assertSee('15');
});

it('can accept quotation from view page', function () {
    $quotation = QuotationModel::factory()->draft()->create();

    expect($quotation->status)->toBe(QuotationStatus::Draft);

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->callAction('accept')
        ->assertNotified();

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Accepted);
    expect($quotation->accepted_at)->not->toBeNull();
});

it('can decline quotation from view page', function () {
    $quotation = QuotationModel::factory()->sent()->create();

    expect($quotation->status)->toBe(QuotationStatus::Sent);

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->callAction('decline')
        ->assertNotified();

    $quotation->refresh();
    expect($quotation->status)->toBe(QuotationStatus::Declined);
    expect($quotation->declined_at)->not->toBeNull();
});

it('hides accept and decline actions when quotation is already accepted', function () {
    $quotation = QuotationModel::factory()->accepted()->create();

    $component = Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()]);

    $component->assertActionHidden('accept');
    $component->assertActionHidden('decline');
});

it('shows convert to invoice action when quotation is accepted', function () {
    $quotation = QuotationModel::factory()->accepted()->create();

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->assertActionVisible('convert_to_invoice');
});

it('hides convert to invoice action when quotation is not accepted', function () {
    $quotation = QuotationModel::factory()->draft()->create();

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->assertActionHidden('convert_to_invoice');
});

it('can access edit page from view page via header action', function () {
    $quotation = QuotationModel::factory()->create();

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->assertActionExists('edit');
});

it('can delete quotation from view page', function () {
    $quotation = QuotationModel::factory()->create();

    Livewire::test(ViewQuotation::class, ['record' => $quotation->getKey()])
        ->callAction('delete')
        ->assertNotified();

    assertSoftDeleted('quotations', ['id' => $quotation->id]);
});
