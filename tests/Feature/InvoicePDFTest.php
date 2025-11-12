<?php

use App\Models\User;
use Application\Invoice\UseCases\GenerateInvoicePDFUseCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceItemModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;
use Infrastructure\State\Persistence\Eloquent\StateModel;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAs(User::factory()->create());
});

it('downloads invoice PDF with valid invoice UUID', function () {
    $invoice = InvoiceModel::factory()->create();

    $response = get(route('invoices.pdf.download', ['uuid' => $invoice->uuid]));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
    $response->assertDownload("invoice-{$invoice->invoice_number}.pdf");
});

it('views invoice PDF with valid invoice UUID', function () {
    $invoice = InvoiceModel::factory()->create();

    $response = get(route('invoices.pdf.view', ['uuid' => $invoice->uuid]));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/pdf');
});

it('returns 404 for non-existent invoice UUID when downloading', function () {
    $response = get(route('invoices.pdf.download', ['uuid' => 'non-existent-uuid-12345']));

    $response->assertNotFound();
});

it('returns 404 for non-existent invoice UUID when viewing', function () {
    $response = get(route('invoices.pdf.view', ['uuid' => 'non-existent-uuid-12345']));

    $response->assertNotFound();
});

it('generates PDF with customer information', function () {
    $customer = CustomerModel::factory()->create([
        'name' => 'ACME Corporation',
        'email' => 'billing@acme.com',
        'phone_primary' => '+60123456789',
    ]);

    $invoice = InvoiceModel::factory()
        ->for($customer, 'customer')
        ->create();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with customer address information', function () {
    $state = StateModel::factory()->create([
        'name' => 'Selangor',
        'code' => 'SEL',
    ]);

    $customer = CustomerModel::factory()->create();

    // Update the existing primary address created by the factory
    $customer->addresses()->update([
        'line1' => '123 Main Street',
        'line2' => 'Suite 100',
        'city' => 'Petaling Jaya',
        'state_id' => $state->id,
        'postcode' => '47400',
    ]);

    $invoice = InvoiceModel::factory()
        ->for($customer, 'customer')
        ->create();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with invoice items', function () {
    $invoice = InvoiceModel::factory()->create();

    InvoiceItemModel::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Web Development Services',
        'quantity' => 10,
        'unit_price' => '150.00',
        'tax_rate' => '10.00',
    ]);

    InvoiceItemModel::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Consulting Hours',
        'quantity' => 5,
        'unit_price' => '200.00',
        'tax_rate' => '10.00',
    ]);

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with correct invoice details', function () {
    $invoice = InvoiceModel::factory()->create([
        'invoice_number' => 'INV-202511-9999',
        'status' => 'Sent',
        'issued_at' => '2025-11-01',
        'due_at' => '2025-12-01',
        'notes' => 'Payment due within 30 days',
    ]);

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with calculated totals', function () {
    $invoice = InvoiceModel::factory()->create([
        'subtotal' => '1000.00',
        'tax_total' => '100.00',
        'total' => '1100.00',
    ]);

    InvoiceItemModel::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Test Item',
        'quantity' => 10,
        'unit_price' => '100.00',
        'tax_rate' => '10.00',
    ]);

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF without items', function () {
    $invoice = InvoiceModel::factory()->create([
        'subtotal' => '0.00',
        'tax_total' => '0.00',
        'total' => '0.00',
    ]);

    // Explicitly ensure no items
    $invoice->items()->delete();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF for invoice without customer address', function () {
    $customer = CustomerModel::factory()->create();

    // Ensure no addresses exist
    $customer->addresses()->delete();

    $invoice = InvoiceModel::factory()
        ->for($customer, 'customer')
        ->create();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with proper filename format for download', function () {
    $invoice = InvoiceModel::factory()->create([
        'invoice_number' => 'INV-202511-TEST',
    ]);

    $response = get(route('invoices.pdf.download', ['uuid' => $invoice->uuid]));

    $response->assertSuccessful();
    $response->assertDownload('invoice-INV-202511-TEST.pdf');
});

it('requires authentication to download PDF', function () {
    auth()->logout();

    $invoice = InvoiceModel::factory()->create();

    $response = get(route('invoices.pdf.download', ['uuid' => $invoice->uuid]));

    $response->assertRedirect(route('filament.admin.auth.login'));
});

it('requires authentication to view PDF', function () {
    auth()->logout();

    $invoice = InvoiceModel::factory()->create();

    $response = get(route('invoices.pdf.view', ['uuid' => $invoice->uuid]));

    $response->assertRedirect(route('filament.admin.auth.login'));
});

it('handles special characters in customer name', function () {
    $customer = CustomerModel::factory()->create([
        'name' => 'Test & Company Sdn. Bhd.',
    ]);

    $invoice = InvoiceModel::factory()
        ->for($customer, 'customer')
        ->create();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
});

it('handles special characters in item descriptions', function () {
    $invoice = InvoiceModel::factory()->create();

    InvoiceItemModel::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Web Design & Development (50% discount)',
    ]);

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with multiple invoice items maintaining order', function () {
    $invoice = InvoiceModel::factory()->create();

    $items = collect([
        'First Item',
        'Second Item',
        'Third Item',
        'Fourth Item',
        'Fifth Item',
    ]);

    foreach ($items as $index => $description) {
        InvoiceItemModel::factory()->create([
            'invoice_id' => $invoice->id,
            'description' => $description,
            'quantity' => $index + 1,
            'unit_price' => '100.00',
        ]);
    }

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('generates PDF with large monetary values', function () {
    $invoice = InvoiceModel::factory()->create([
        'subtotal' => '999999.99',
        'tax_total' => '99999.99',
        'total' => '1099999.98',
    ]);

    InvoiceItemModel::factory()->create([
        'invoice_id' => $invoice->id,
        'description' => 'Large Project',
        'quantity' => 1,
        'unit_price' => '999999.99',
    ]);

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
});

it('executes use case directly for download', function () {
    $invoice = InvoiceModel::factory()->create();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->execute($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('attachment');
    expect($response->headers->get('Content-Disposition'))->toContain($invoice->invoice_number);
});

it('executes use case directly for streaming', function () {
    $invoice = InvoiceModel::factory()->create();

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
    expect($response->headers->get('Content-Type'))->toContain('application/pdf');
    expect($response->headers->get('Content-Disposition'))->toContain('inline');
    expect($response->headers->get('Content-Disposition'))->toContain($invoice->invoice_number);
});

it('generates different response headers for download vs stream', function () {
    $invoice = InvoiceModel::factory()->create();
    $useCase = app(GenerateInvoicePDFUseCase::class);

    $downloadResponse = $useCase->execute($invoice->uuid);
    $streamResponse = $useCase->stream($invoice->uuid);

    // Download should have attachment disposition
    expect($downloadResponse->headers->get('Content-Disposition'))
        ->toContain('attachment');

    // Stream should have inline disposition
    expect($streamResponse->headers->get('Content-Disposition'))
        ->toContain('inline');
});

it('generates PDF with all invoice statuses', function () {
    $statuses = ['Draft', 'Sent', 'Paid', 'Overdue', 'Cancelled', 'Void'];

    foreach ($statuses as $status) {
        $invoice = InvoiceModel::factory()->create(['status' => $status]);

        $useCase = app(GenerateInvoicePDFUseCase::class);
        $response = $useCase->stream($invoice->uuid);

        expect($response->getStatusCode())->toBe(200);
    }
});

it('generates PDF with paid invoice including payment date', function () {
    $invoice = InvoiceModel::factory()->paid()->create([
        'paid_at' => '2025-11-15',
    ]);

    $useCase = app(GenerateInvoicePDFUseCase::class);
    $response = $useCase->stream($invoice->uuid);

    expect($response->getStatusCode())->toBe(200);
});
