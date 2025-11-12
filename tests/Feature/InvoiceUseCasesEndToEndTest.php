<?php

use App\Enums\InvoiceStatus;
use Application\Invoice\DTOs\CreateInvoiceDTO;
use Application\Invoice\DTOs\CreateInvoiceItemDTO;
use Application\Invoice\DTOs\UpdateInvoiceDTO;
use Application\Invoice\UseCases\CreateInvoiceUseCase;
use Application\Invoice\UseCases\DeleteInvoiceUseCase;
use Application\Invoice\UseCases\GetInvoiceUseCase;
use Application\Invoice\UseCases\ListInvoicesUseCase;
use Application\Invoice\UseCases\MarkInvoiceAsPaidUseCase;
use Application\Invoice\UseCases\UpdateInvoiceUseCase;
use DateTimeImmutable;
use Domain\Invoice\Entities\Invoice;
use Domain\Invoice\Exceptions\InvoiceNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;
use Infrastructure\Invoice\Repositories\EloquentInvoiceRepository;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

describe('CreateInvoiceUseCase', function () {
    it('creates an invoice without items', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new CreateInvoiceUseCase($repository);

        $dto = new CreateInvoiceDTO(
            customerId: $customer->id,
            invoiceNumber: 'INV-202511-0001',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            dueAt: new DateTimeImmutable('2025-12-01'),
            notes: 'Payment terms: Net 30'
        );

        $invoice = $useCase->execute($dto);

        expect($invoice)->toBeInstanceOf(Invoice::class)
            ->and($invoice->id())->not->toBeNull()
            ->and($invoice->customerId())->toBe($customer->id)
            ->and($invoice->invoiceNumber()->value())->toBe('INV-202511-0001')
            ->and($invoice->status())->toBe(InvoiceStatus::Draft)
            ->and($invoice->issuedAt()->format('Y-m-d'))->toBe('2025-11-01')
            ->and($invoice->dueAt()->format('Y-m-d'))->toBe('2025-12-01')
            ->and($invoice->notes())->toBe('Payment terms: Net 30')
            ->and($invoice->items())->toBeEmpty();

        assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-202511-0001',
            'status' => 'Draft',
            'notes' => 'Payment terms: Net 30',
        ]);
    });

    it('creates an invoice with items', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new CreateInvoiceUseCase($repository);

        $dto = new CreateInvoiceDTO(
            customerId: $customer->id,
            invoiceNumber: 'INV-202511-0002',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            dueAt: new DateTimeImmutable('2025-12-01'),
            items: [
                new CreateInvoiceItemDTO(
                    description: 'Web Development',
                    quantity: 10,
                    unitPrice: '150.00',
                    taxRate: '10'
                ),
                new CreateInvoiceItemDTO(
                    description: 'Consulting',
                    quantity: 5,
                    unitPrice: '200.00',
                    taxRate: '10'
                ),
            ]
        );

        $invoice = $useCase->execute($dto);

        expect($invoice->items())->toHaveCount(2);

        assertDatabaseHas('invoices', [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-202511-0002',
        ]);

        assertDatabaseCount('invoice_items', 2);

        assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id(),
            'description' => 'Web Development',
            'quantity' => 10,
            'unit_price' => '150.00',
            'tax_rate' => '10.00',
        ]);

        assertDatabaseHas('invoice_items', [
            'invoice_id' => $invoice->id(),
            'description' => 'Consulting',
            'quantity' => 5,
            'unit_price' => '200.00',
            'tax_rate' => '10.00',
        ]);
    });

    it('creates invoice using fromArray method on DTO', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new CreateInvoiceUseCase($repository);

        $data = [
            'customer_id' => $customer->id,
            'invoice_number' => 'INV-202511-0003',
            'issued_at' => '2025-11-01',
            'due_at' => '2025-12-01',
            'notes' => 'Array creation test',
            'items' => [
                [
                    'description' => 'Product A',
                    'quantity' => 3,
                    'unit_price' => '100.00',
                    'tax_rate' => '8',
                ],
            ],
        ];

        $dto = CreateInvoiceDTO::fromArray($data);
        $invoice = $useCase->execute($dto);

        expect($invoice->invoiceNumber()->value())->toBe('INV-202511-0003')
            ->and($invoice->notes())->toBe('Array creation test')
            ->and($invoice->items())->toHaveCount(1);
    });
});

describe('UpdateInvoiceUseCase', function () {
    it('updates invoice notes', function () {
        $invoice = InvoiceModel::factory()->draft()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new UpdateInvoiceUseCase($repository);

        $dto = new UpdateInvoiceDTO(notes: 'Updated notes for invoice');

        $updatedInvoice = $useCase->execute($invoice->id, $dto);

        assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'notes' => 'Updated notes for invoice',
        ]);

        expect($updatedInvoice->notes())->toBe('Updated notes for invoice');
    });

    it('updates invoice due date', function () {
        $invoice = InvoiceModel::factory()->draft()->create([
            'issued_at' => new DateTimeImmutable('2025-11-01'),
            'due_at' => new DateTimeImmutable('2025-12-01'),
        ]);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new UpdateInvoiceUseCase($repository);

        $dto = new UpdateInvoiceDTO(
            dueAt: new DateTimeImmutable('2025-12-15')
        );

        $updatedInvoice = $useCase->execute($invoice->id, $dto);

        expect($updatedInvoice->dueAt()->format('Y-m-d'))->toBe('2025-12-15')
            ->and($updatedInvoice->issuedAt()->format('Y-m-d'))->toBe('2025-11-01'); // Issued date unchanged
    });

    it('throws exception when updating non-existent invoice', function () {
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new UpdateInvoiceUseCase($repository);

        $dto = new UpdateInvoiceDTO(notes: 'Test');

        $useCase->execute(99999, $dto);
    })->throws(InvoiceNotFoundException::class);
});

describe('MarkInvoiceAsPaidUseCase', function () {
    it('marks invoice as paid with default date', function () {
        $invoice = InvoiceModel::factory()->sent()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new MarkInvoiceAsPaidUseCase($repository);

        $result = $useCase->execute($invoice->id);

        expect($result)->toBeTrue();

        $updated = InvoiceModel::find($invoice->id);
        expect($updated)->not->toBeNull();
        expect($updated?->status)->toBe(InvoiceStatus::Paid);
        expect($updated?->paid_at)->not->toBeNull();
    });

    it('marks invoice as paid with custom date', function () {
        $invoice = InvoiceModel::factory()->sent()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new MarkInvoiceAsPaidUseCase($repository);

        $paidAt = new DateTimeImmutable('2025-11-10');
        $result = $useCase->execute($invoice->id, $paidAt);

        expect($result)->toBeTrue();

        $updated = InvoiceModel::find($invoice->id);
        expect($updated)->not->toBeNull();
        expect($updated?->status)->toBe(InvoiceStatus::Paid);

        assert($updated !== null);
        /** @var \Illuminate\Support\Carbon $paidAtDate */
        $paidAtDate = $updated->paid_at;
        assert($paidAtDate !== null);
        expect($paidAtDate->format('Y-m-d'))->toBe('2025-11-10');
    });

    it('marks invoice as paid with payment method and reference', function () {
        $invoice = InvoiceModel::factory()->sent()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new MarkInvoiceAsPaidUseCase($repository);

        $paidAt = new DateTimeImmutable('2025-11-15');
        $result = $useCase->execute(
            $invoice->id,
            $paidAt,
            'Credit Card',
            'TXN-123456789'
        );

        expect($result)->toBeTrue();

        $updated = InvoiceModel::find($invoice->id);
        expect($updated)->not->toBeNull();
        expect($updated?->status)->toBe(InvoiceStatus::Paid);
        expect($updated?->paymentMethod?->name)->toBe('Credit Card');
        expect($updated?->payment_reference)->toBe('TXN-123456789');

        assert($updated !== null);
        /** @var \Illuminate\Support\Carbon $paidAtDate */
        $paidAtDate = $updated->paid_at;
        assert($paidAtDate !== null);
        expect($paidAtDate->format('Y-m-d'))->toBe('2025-11-15');
    });

    it('marks invoice as paid with only payment method', function () {
        $invoice = InvoiceModel::factory()->sent()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new MarkInvoiceAsPaidUseCase($repository);

        $result = $useCase->execute(
            $invoice->id,
            null,
            'Bank Transfer'
        );

        expect($result)->toBeTrue();

        $updated = InvoiceModel::find($invoice->id);
        expect($updated)->not->toBeNull();
        expect($updated?->paymentMethod?->name)->toBe('Bank Transfer');
        expect($updated?->payment_reference)->toBeNull();
    });

    it('stores payment details in domain entity and persists correctly', function () {
        $invoice = InvoiceModel::factory()->sent()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new MarkInvoiceAsPaidUseCase($repository);
        $getUseCase = new GetInvoiceUseCase($repository);

        $paidAt = new DateTimeImmutable('2025-11-20');
        $useCase->execute(
            $invoice->id,
            $paidAt,
            'Cash',
            'RECEIPT-001'
        );

        // Retrieve the domain entity and verify payment details
        $domainInvoice = $getUseCase->execute($invoice->id);

        expect($domainInvoice->isPaid())->toBeTrue()
            ->and($domainInvoice->paymentMethod())->toBe('Cash')
            ->and($domainInvoice->paymentReference())->toBe('RECEIPT-001')
            ->and($domainInvoice->paidAt()?->format('Y-m-d'))->toBe('2025-11-20');
    });

    it('throws exception when marking non-existent invoice as paid', function () {
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new MarkInvoiceAsPaidUseCase($repository);

        $useCase->execute(99999);
    })->throws(InvoiceNotFoundException::class);
});

describe('GetInvoiceUseCase', function () {
    it('retrieves an invoice by id', function () {
        $invoiceModel = InvoiceModel::factory()->create([
            'invoice_number' => 'INV-202511-0010',
            'notes' => 'Test invoice',
        ]);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new GetInvoiceUseCase($repository);

        $invoice = $useCase->execute($invoiceModel->id);

        expect($invoice)->toBeInstanceOf(Invoice::class)
            ->and($invoice->id())->toBe($invoiceModel->id)
            ->and($invoice->invoiceNumber()->value())->toBe('INV-202511-0010')
            ->and($invoice->notes())->toBe('Test invoice');
    });

    it('retrieves invoice with items', function () {
        $invoiceModel = InvoiceModel::factory()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new GetInvoiceUseCase($repository);

        $invoice = $useCase->execute($invoiceModel->id);

        // Factory creates 1-5 items by default
        expect($invoice->items())->not->toBeEmpty();
    });

    it('throws exception when invoice not found', function () {
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new GetInvoiceUseCase($repository);

        $useCase->execute(99999);
    })->throws(InvoiceNotFoundException::class);
});

describe('DeleteInvoiceUseCase', function () {
    it('soft deletes an invoice', function () {
        $invoice = InvoiceModel::factory()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new DeleteInvoiceUseCase($repository);

        $result = $useCase->execute($invoice->id);

        expect($result)->toBeTrue();

        assertDatabaseHas('invoices', ['id' => $invoice->id]);

        $freshInvoice = $invoice->fresh();
        expect($freshInvoice)->not->toBeNull();

        /** @var \Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel $freshInvoice */
        expect($freshInvoice->deleted_at)->not->toBeNull();
    });

    it('throws exception when deleting non-existent invoice', function () {
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new DeleteInvoiceUseCase($repository);

        $useCase->execute(99999);
    })->throws(InvoiceNotFoundException::class);
});

describe('ListInvoicesUseCase', function () {
    it('returns all invoices when no filters provided', function () {
        InvoiceModel::factory()->count(5)->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute();

        expect($invoices)->toHaveCount(5)
            ->and($invoices[0])->toBeInstanceOf(Invoice::class);
    });

    it('returns empty array when no invoices exist', function () {
        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute();

        expect($invoices)->toBeEmpty();
    });

    it('filters invoices by customer_id', function () {
        $customer1 = CustomerModel::factory()->create();
        $customer2 = CustomerModel::factory()->create();

        InvoiceModel::factory()->count(3)->create(['customer_id' => $customer1->id]);
        InvoiceModel::factory()->count(2)->create(['customer_id' => $customer2->id]);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute(['customer_id' => $customer1->id]);

        expect($invoices)->toHaveCount(3);
    });

    it('filters invoices by status', function () {
        InvoiceModel::factory()->count(2)->draft()->create();
        InvoiceModel::factory()->count(3)->sent()->create();
        InvoiceModel::factory()->count(1)->paid()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute(['status' => InvoiceStatus::Sent]);

        expect($invoices)->toHaveCount(3);
    });

    it('filters invoices by invoice_number', function () {
        InvoiceModel::factory()->create(['invoice_number' => 'INV-202511-0001']);
        InvoiceModel::factory()->create(['invoice_number' => 'INV-202511-0002']);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute(['invoice_number' => 'INV-202511-0001']);

        expect($invoices)->toHaveCount(1)
            ->and($invoices[0]->invoiceNumber()->value())->toBe('INV-202511-0001');
    });

    it('filters invoices by date range', function () {
        InvoiceModel::factory()->create(['issued_at' => new DateTimeImmutable('2025-11-01')]);
        InvoiceModel::factory()->create(['issued_at' => new DateTimeImmutable('2025-11-15')]);
        InvoiceModel::factory()->create(['issued_at' => new DateTimeImmutable('2025-12-01')]);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute([
            'issued_from' => new DateTimeImmutable('2025-11-10'),
            'issued_to' => new DateTimeImmutable('2025-11-20'),
        ]);

        expect($invoices)->toHaveCount(1);
    });

    it('filters overdue invoices', function () {
        InvoiceModel::factory()->overdue()->count(2)->create();
        InvoiceModel::factory()->count(3)->create(['due_at' => new DateTimeImmutable('+30 days')]);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute(['overdue' => true]);

        expect($invoices)->toHaveCount(2);
    });

    it('filters invoices with multiple criteria', function () {
        $customer = CustomerModel::factory()->create();

        InvoiceModel::factory()->create([
            'customer_id' => $customer->id,
            'status' => InvoiceStatus::Sent,
            'issued_at' => new DateTimeImmutable('2025-11-15'),
        ]);

        InvoiceModel::factory()->create([
            'customer_id' => $customer->id,
            'status' => InvoiceStatus::Paid,
            'issued_at' => new DateTimeImmutable('2025-11-15'),
        ]);

        InvoiceModel::factory()->create([
            'customer_id' => $customer->id,
            'status' => InvoiceStatus::Sent,
            'issued_at' => new DateTimeImmutable('2025-12-01'),
        ]);

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute([
            'customer_id' => $customer->id,
            'status' => InvoiceStatus::Sent,
        ]);

        expect($invoices)->toHaveCount(2);
    });

    it('returns invoices with items loaded', function () {
        InvoiceModel::factory()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $useCase = new ListInvoicesUseCase($repository);

        $invoices = $useCase->execute();

        expect($invoices)->toHaveCount(1)
            ->and($invoices[0]->items())->not->toBeEmpty();
    });
});

describe('End-to-End Complete Flow', function () {
    it('completes full CRUD lifecycle', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentInvoiceRepository::class);

        // CREATE
        $createUseCase = new CreateInvoiceUseCase($repository);
        $createDto = new CreateInvoiceDTO(
            customerId: $customer->id,
            invoiceNumber: 'INV-202511-9999',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            dueAt: new DateTimeImmutable('2025-12-01'),
            notes: 'Lifecycle test invoice',
            items: [
                new CreateInvoiceItemDTO(
                    description: 'Product A',
                    quantity: 2,
                    unitPrice: '100.00',
                    taxRate: '10'
                ),
            ]
        );

        $invoice = $createUseCase->execute($createDto);
        $invoiceId = $invoice->id();

        assertDatabaseHas('invoices', ['invoice_number' => 'INV-202511-9999']);
        assertDatabaseCount('invoice_items', 1);

        // READ
        $getUseCase = new GetInvoiceUseCase($repository);
        /** @var int $invoiceId */
        $retrievedInvoice = $getUseCase->execute($invoiceId);

        expect($retrievedInvoice->invoiceNumber()->value())->toBe('INV-202511-9999')
            ->and($retrievedInvoice->items())->toHaveCount(1);

        // UPDATE
        $updateUseCase = new UpdateInvoiceUseCase($repository);
        $updateDto = new UpdateInvoiceDTO(notes: 'Updated lifecycle notes');

        /** @var int $invoiceId */
        $updatedInvoice = $updateUseCase->execute($invoiceId, $updateDto);

        expect($updatedInvoice->notes())->toBe('Updated lifecycle notes');

        // MARK AS PAID
        $markPaidUseCase = new MarkInvoiceAsPaidUseCase($repository);
        /** @var int $invoiceId */
        $markPaidUseCase->execute($invoiceId);

        /** @var int $invoiceId */
        $paidInvoice = $getUseCase->execute($invoiceId);
        expect($paidInvoice->status())->toBe(InvoiceStatus::Paid);

        // LIST
        $listUseCase = new ListInvoicesUseCase($repository);
        $invoices = $listUseCase->execute(['status' => InvoiceStatus::Paid]);

        expect($invoices)->toHaveCount(1)
            ->and($invoices[0]->invoiceNumber()->value())->toBe('INV-202511-9999');

        // DELETE
        $deleteUseCase = new DeleteInvoiceUseCase($repository);
        /** @var int $invoiceId */
        $result = $deleteUseCase->execute($invoiceId);

        expect($result)->toBeTrue();

        $deletedInvoices = $listUseCase->execute(['status' => InvoiceStatus::Paid]);
        expect($deletedInvoices)->toBeEmpty();
    });

    it('handles multiple concurrent invoices correctly', function () {
        $customer1 = CustomerModel::factory()->create();
        $customer2 = CustomerModel::factory()->create();

        $repository = app(EloquentInvoiceRepository::class);
        $createUseCase = new CreateInvoiceUseCase($repository);

        // Create invoices for different customers
        $dto1 = new CreateInvoiceDTO(
            customerId: $customer1->id,
            invoiceNumber: 'INV-202511-1001',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            dueAt: new DateTimeImmutable('2025-12-01')
        );

        $dto2 = new CreateInvoiceDTO(
            customerId: $customer2->id,
            invoiceNumber: 'INV-202511-1002',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            dueAt: new DateTimeImmutable('2025-12-01')
        );

        $invoice1 = $createUseCase->execute($dto1);
        $invoice2 = $createUseCase->execute($dto2);

        // Mark first invoice as paid
        $markPaidUseCase = new MarkInvoiceAsPaidUseCase($repository);
        $invoice1Id = $invoice1->id();
        /** @var int $invoice1Id */
        $markPaidUseCase->execute($invoice1Id);

        // Verify both invoices exist with correct states
        $listUseCase = new ListInvoicesUseCase($repository);
        $allInvoices = $listUseCase->execute();

        expect($allInvoices)->toHaveCount(2);

        $customer1Invoices = $listUseCase->execute(['customer_id' => $customer1->id]);
        expect($customer1Invoices)->toHaveCount(1)
            ->and($customer1Invoices[0]->status())->toBe(InvoiceStatus::Paid);

        $customer2Invoices = $listUseCase->execute(['customer_id' => $customer2->id]);
        expect($customer2Invoices)->toHaveCount(1)
            ->and($customer2Invoices[0]->status())->toBe(InvoiceStatus::Draft);
    });
});
