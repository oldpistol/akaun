<?php

use App\Enums\QuotationStatus;
use Application\Quotation\DTOs\CreateQuotationDTO;
use Application\Quotation\DTOs\CreateQuotationItemDTO;
use Application\Quotation\DTOs\UpdateQuotationDTO;
use Application\Quotation\UseCases\AcceptQuotationUseCase;
use Application\Quotation\UseCases\ConvertQuotationToInvoiceUseCase;
use Application\Quotation\UseCases\CreateQuotationUseCase;
use Application\Quotation\UseCases\DeclineQuotationUseCase;
use Application\Quotation\UseCases\DeleteQuotationUseCase;
use Application\Quotation\UseCases\GetQuotationUseCase;
use Application\Quotation\UseCases\ListQuotationsUseCase;
use Application\Quotation\UseCases\UpdateQuotationUseCase;
use Domain\Invoice\Entities\Invoice;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Invoice\Repositories\EloquentInvoiceRepository;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;
use Infrastructure\Quotation\Repositories\EloquentQuotationRepository;

use function Pest\Laravel\assertDatabaseCount;
use function Pest\Laravel\assertDatabaseHas;

uses(RefreshDatabase::class);

describe('CreateQuotationUseCase', function () {
    it('creates a quotation without items', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new CreateQuotationUseCase($repository);

        $dto = new CreateQuotationDTO(
            customerId: $customer->id,
            quotationNumber: 'QT-202511-0001',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            validUntil: new DateTimeImmutable('2025-12-01'),
            notes: 'Important client quotation'
        );

        $quotation = $useCase->execute($dto);

        expect($quotation)->toBeInstanceOf(Quotation::class)
            ->and($quotation->id())->not->toBeNull()
            ->and($quotation->customerId())->toBe($customer->id)
            ->and($quotation->quotationNumber()->value())->toBe('QT-202511-0001')
            ->and($quotation->status())->toBe(QuotationStatus::Draft)
            ->and($quotation->issuedAt()->format('Y-m-d'))->toBe('2025-11-01')
            ->and($quotation->validUntil()->format('Y-m-d'))->toBe('2025-12-01')
            ->and($quotation->notes())->toBe('Important client quotation')
            ->and($quotation->items())->toBeEmpty();

        assertDatabaseHas('quotations', [
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-202511-0001',
            'status' => 'Draft',
            'notes' => 'Important client quotation',
        ]);
    });

    it('creates a quotation with items', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new CreateQuotationUseCase($repository);

        $dto = new CreateQuotationDTO(
            customerId: $customer->id,
            quotationNumber: 'QT-202511-0002',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            validUntil: new DateTimeImmutable('2025-12-01'),
            items: [
                new CreateQuotationItemDTO(
                    description: 'Web Development',
                    quantity: 10,
                    unitPrice: '150.00',
                    taxRate: '10'
                ),
                new CreateQuotationItemDTO(
                    description: 'Consulting',
                    quantity: 5,
                    unitPrice: '200.00',
                    taxRate: '10'
                ),
            ]
        );

        $quotation = $useCase->execute($dto);

        expect($quotation->items())->toHaveCount(2);

        assertDatabaseHas('quotations', [
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-202511-0002',
        ]);

        assertDatabaseCount('quotation_items', 2);

        assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id(),
            'description' => 'Web Development',
            'quantity' => 10,
            'unit_price' => '150.00',
            'tax_rate' => '10.00',
        ]);

        assertDatabaseHas('quotation_items', [
            'quotation_id' => $quotation->id(),
            'description' => 'Consulting',
            'quantity' => 5,
            'unit_price' => '200.00',
            'tax_rate' => '10.00',
        ]);
    });

    it('creates quotation with discount', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new CreateQuotationUseCase($repository);

        $dto = new CreateQuotationDTO(
            customerId: $customer->id,
            quotationNumber: 'QT-202511-0003',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            validUntil: new DateTimeImmutable('2025-12-01'),
            discountPercentage: '10.00',
            items: [
                new CreateQuotationItemDTO(
                    description: 'Product A',
                    quantity: 1,
                    unitPrice: '1000.00',
                    taxRate: '0'
                ),
            ]
        );

        $quotation = $useCase->execute($dto);

        expect($quotation->discountPercentage()->value())->toBe('10.00')
            ->and($quotation->subtotal()->amount())->toBe('1000.00')
            ->and($quotation->total()->amount())->toBe('900.00');

        assertDatabaseHas('quotations', [
            'quotation_number' => 'QT-202511-0003',
            'discount_rate' => '10.00',
        ]);
    });

    it('creates quotation using fromArray method on DTO', function () {
        $customer = CustomerModel::factory()->create();
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new CreateQuotationUseCase($repository);

        $data = [
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-202511-0004',
            'issued_at' => '2025-11-01',
            'valid_until' => '2025-12-01',
            'notes' => 'Array creation test',
            'discount_percentage' => '5.00',
            'items' => [
                [
                    'description' => 'Product A',
                    'quantity' => 3,
                    'unit_price' => '100.00',
                    'tax_rate' => '8',
                ],
            ],
        ];

        $dto = CreateQuotationDTO::fromArray($data);
        $quotation = $useCase->execute($dto);

        expect($quotation->quotationNumber()->value())->toBe('QT-202511-0004')
            ->and($quotation->notes())->toBe('Array creation test')
            ->and($quotation->discountPercentage()->value())->toBe('5.00')
            ->and($quotation->items())->toHaveCount(1);
    });
});

describe('UpdateQuotationUseCase', function () {
    it('updates quotation notes', function () {
        $quotation = QuotationModel::factory()->create();

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new UpdateQuotationUseCase($repository);

        $dto = new UpdateQuotationDTO(notes: 'Updated notes for quotation');

        $updatedQuotation = $useCase->execute($quotation->id, $dto);

        assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'notes' => 'Updated notes for quotation',
        ]);

        expect($updatedQuotation->notes())->toBe('Updated notes for quotation');
    });

    it('updates quotation valid until date', function () {
        $quotation = QuotationModel::factory()
            ->state(['status' => QuotationStatus::Draft])
            ->create([
                'issued_at' => new DateTimeImmutable('2025-11-01'),
                'valid_until' => new DateTimeImmutable('2025-12-01'),
            ]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new UpdateQuotationUseCase($repository);

        $dto = new UpdateQuotationDTO(
            validUntil: new DateTimeImmutable('2025-12-15')
        );

        $updatedQuotation = $useCase->execute($quotation->id, $dto);

        expect($updatedQuotation->validUntil()->format('Y-m-d'))->toBe('2025-12-15')
            ->and($updatedQuotation->issuedAt()->format('Y-m-d'))->toBe('2025-11-01');
    });

    it('updates quotation discount percentage', function () {
        $quotation = QuotationModel::factory()
            ->state(['status' => QuotationStatus::Draft])
            ->create(['discount_rate' => '0.00']);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new UpdateQuotationUseCase($repository);

        $dto = new UpdateQuotationDTO(discountPercentage: '15.00');

        $updatedQuotation = $useCase->execute($quotation->id, $dto);

        expect($updatedQuotation->discountPercentage()->value())->toBe('15.00');

        assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'discount_rate' => '15.00',
        ]);
    });

    it('throws exception when updating non-existent quotation', function () {
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new UpdateQuotationUseCase($repository);

        $dto = new UpdateQuotationDTO(notes: 'Test');

        $useCase->execute(99999, $dto);
    })->throws(QuotationNotFoundException::class);
});

describe('AcceptQuotationUseCase', function () {
    it('accepts a draft quotation', function () {
        $quotation = QuotationModel::factory()->create([
            'status' => QuotationStatus::Draft,
            'valid_until' => now()->addDays(30), // Future date to prevent expiration
        ]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new AcceptQuotationUseCase($repository);

        $acceptedQuotation = $useCase->execute($quotation->id);

        expect($acceptedQuotation->status())->toBe(QuotationStatus::Accepted)
            ->and($acceptedQuotation->acceptedAt())->not->toBeNull();

        assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'Accepted',
        ]);
    });
});

describe('DeclineQuotationUseCase', function () {
    it('declines a draft quotation', function () {
        $quotation = QuotationModel::factory()->create([
            'status' => QuotationStatus::Draft,
        ]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new DeclineQuotationUseCase($repository);

        $declinedQuotation = $useCase->execute($quotation->id);

        expect($declinedQuotation->status())->toBe(QuotationStatus::Declined)
            ->and($declinedQuotation->declinedAt())->not->toBeNull();

        assertDatabaseHas('quotations', [
            'id' => $quotation->id,
            'status' => 'Declined',
        ]);
    });
});

describe('GetQuotationUseCase', function () {
    it('retrieves a quotation by id', function () {
        $quotationModel = QuotationModel::factory()->create([
            'quotation_number' => 'QT-202511-0005',
        ]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new GetQuotationUseCase($repository);

        $quotation = $useCase->execute($quotationModel->id);

        expect($quotation)->toBeInstanceOf(Quotation::class)
            ->and($quotation->id())->toBe($quotationModel->id)
            ->and($quotation->quotationNumber()->value())->toBe('QT-202511-0005');
    });

    it('retrieves quotation with items', function () {
        $customer = CustomerModel::factory()->create();

        // Use raw SQL to insert without triggering factory hooks
        $quotationId = DB::table('quotations')->insertGetId([
            'uuid' => (string) \Domain\Customer\ValueObjects\Uuid::generate(),
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-TEST-001',
            'status' => 'Draft',
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'subtotal' => '0',
            'tax_total' => '0',
            'discount_rate' => '0',
            'discount_amount' => '0',
            'total' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Infrastructure\Quotation\Persistence\Eloquent\QuotationItemModel::factory()->count(2)->create([
            'quotation_id' => $quotationId,
        ]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new GetQuotationUseCase($repository);

        $result = $useCase->execute($quotationId);

        expect($result->items())->toHaveCount(2);
    });

    it('throws exception when quotation not found', function () {
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new GetQuotationUseCase($repository);

        $useCase->execute(99999);
    })->throws(QuotationNotFoundException::class);
});

describe('DeleteQuotationUseCase', function () {
    it('deletes a quotation', function () {
        // Create quotation using factory but delete auto-created items
        $quotation = QuotationModel::factory()->create();

        // Remove auto-created items so we have clean state
        $quotation->items()->delete();

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new DeleteQuotationUseCase($repository);

        $result = $useCase->execute($quotation->id);

        expect($result)->toBeTrue();

        // Verify soft delete - record still exists in DB
        assertDatabaseHas('quotations', ['id' => $quotation->id]);

        // Verify deleted_at is set
        $freshQuotation = QuotationModel::withTrashed()->find($quotation->id);
        expect($freshQuotation)->not->toBeNull()
            ->and($freshQuotation->deleted_at)->not->toBeNull();
    });

    it('throws exception when deleting non-existent quotation', function () {
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new DeleteQuotationUseCase($repository);

        $useCase->execute(99999);
    })->throws(QuotationNotFoundException::class);
});

describe('ListQuotationsUseCase', function () {
    it('returns all quotations when no filters provided', function () {
        QuotationModel::factory()->count(5)->create();

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new ListQuotationsUseCase($repository);

        $quotations = $useCase->execute();

        expect($quotations)->toHaveCount(5)
            ->and($quotations[0])->toBeInstanceOf(Quotation::class);
    });

    it('returns empty array when no quotations exist', function () {
        $repository = app(EloquentQuotationRepository::class);
        $useCase = new ListQuotationsUseCase($repository);

        $quotations = $useCase->execute();

        expect($quotations)->toBeEmpty();
    });

    it('filters quotations by customer id', function () {
        $customer1 = CustomerModel::factory()->create();
        $customer2 = CustomerModel::factory()->create();

        QuotationModel::factory()->count(3)->create(['customer_id' => $customer1->id]);
        QuotationModel::factory()->count(2)->create(['customer_id' => $customer2->id]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new ListQuotationsUseCase($repository);

        $quotations = $useCase->search(['customer_id' => $customer1->id]);

        expect($quotations)->toHaveCount(3);
    });

    it('filters quotations by status', function () {
        QuotationModel::factory()->count(2)->create(['status' => QuotationStatus::Draft]);
        QuotationModel::factory()->count(3)->create(['status' => QuotationStatus::Accepted]);

        $repository = app(EloquentQuotationRepository::class);
        $useCase = new ListQuotationsUseCase($repository);

        $quotations = $useCase->search(['status' => QuotationStatus::Accepted]);

        expect($quotations)->toHaveCount(3);
    });
});

describe('ConvertQuotationToInvoiceUseCase', function () {
    it('converts an accepted quotation to invoice', function () {
        $customer = CustomerModel::factory()->create();

        // Use raw SQL to insert without triggering factory hooks
        $quotationId = DB::table('quotations')->insertGetId([
            'uuid' => (string) \Domain\Customer\ValueObjects\Uuid::generate(),
            'customer_id' => $customer->id,
            'quotation_number' => 'QT-CONVERT-001',
            'status' => 'Accepted',
            'issued_at' => now(),
            'valid_until' => now()->addDays(30),
            'accepted_at' => now(),
            'subtotal' => '0',
            'tax_total' => '0',
            'discount_rate' => '0',
            'discount_amount' => '0',
            'total' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        \Infrastructure\Quotation\Persistence\Eloquent\QuotationItemModel::factory()->count(2)->create([
            'quotation_id' => $quotationId,
        ]);

        $quotationRepository = app(EloquentQuotationRepository::class);
        $invoiceRepository = app(EloquentInvoiceRepository::class);
        $createInvoiceUseCase = new \Application\Invoice\UseCases\CreateInvoiceUseCase($invoiceRepository);

        $useCase = new ConvertQuotationToInvoiceUseCase(
            $quotationRepository,
            $invoiceRepository,
            $createInvoiceUseCase
        );

        $invoice = $useCase->execute($quotationId);

        expect($invoice)->toBeInstanceOf(Invoice::class)
            ->and($invoice->id())->not->toBeNull()
            ->and($invoice->customerId())->toBe($customer->id)
            ->and($invoice->items())->toHaveCount(2);

        // Verify quotation is marked as converted
        $updatedQuotation = $quotationRepository->findById($quotationId);
        expect($updatedQuotation->status())->toBe(QuotationStatus::Converted)
            ->and($updatedQuotation->convertedAt())->not->toBeNull()
            ->and($updatedQuotation->convertedInvoiceId())->toBe($invoice->id());

        assertDatabaseHas('quotations', [
            'id' => $quotationId,
            'status' => 'Converted',
            'converted_invoice_id' => $invoice->id(),
        ]);

        assertDatabaseHas('invoices', [
            'id' => $invoice->id(),
            'customer_id' => $customer->id,
        ]);
    });
});

describe('End-to-End Complete Flow', function () {
    it('completes full quotation lifecycle', function () {
        $customer = CustomerModel::factory()->create();
        $quotationRepository = app(EloquentQuotationRepository::class);
        $invoiceRepository = app(EloquentInvoiceRepository::class);

        // CREATE
        $createUseCase = new CreateQuotationUseCase($quotationRepository);
        $createDto = new CreateQuotationDTO(
            customerId: $customer->id,
            quotationNumber: 'QT-LIFECYCLE-001',
            issuedAt: new DateTimeImmutable('2025-11-01'),
            validUntil: new DateTimeImmutable('2025-12-01'),
            notes: 'Lifecycle test quotation',
            discountPercentage: '10.00',
            items: [
                new CreateQuotationItemDTO(
                    description: 'Service A',
                    quantity: 2,
                    unitPrice: '500.00',
                    taxRate: '10'
                ),
            ]
        );

        $quotation = $createUseCase->execute($createDto);
        $quotationId = $quotation->id();

        assertDatabaseHas('quotations', ['quotation_number' => 'QT-LIFECYCLE-001']);
        assertDatabaseCount('quotation_items', 1);

        // READ
        $getUseCase = new GetQuotationUseCase($quotationRepository);
        $retrievedQuotation = $getUseCase->execute($quotationId);

        expect($retrievedQuotation->quotationNumber()->value())->toBe('QT-LIFECYCLE-001')
            ->and($retrievedQuotation->items())->toHaveCount(1);

        // UPDATE
        $updateUseCase = new UpdateQuotationUseCase($quotationRepository);
        $updateDto = new UpdateQuotationDTO(
            notes: 'Updated lifecycle quotation',
            discountPercentage: '15.00'
        );

        $updatedQuotation = $updateUseCase->execute($quotationId, $updateDto);

        expect($updatedQuotation->notes())->toBe('Updated lifecycle quotation')
            ->and($updatedQuotation->discountPercentage()->value())->toBe('15.00');

        // ACCEPT
        $acceptUseCase = new AcceptQuotationUseCase($quotationRepository);
        $acceptedQuotation = $acceptUseCase->execute($quotationId);

        expect($acceptedQuotation->status())->toBe(QuotationStatus::Accepted);

        // CONVERT TO INVOICE
        $createInvoiceUseCase = new \Application\Invoice\UseCases\CreateInvoiceUseCase($invoiceRepository);
        $convertUseCase = new ConvertQuotationToInvoiceUseCase(
            $quotationRepository,
            $invoiceRepository,
            $createInvoiceUseCase
        );

        $invoice = $convertUseCase->execute($quotationId);

        expect($invoice)->toBeInstanceOf(Invoice::class)
            ->and($invoice->customerId())->toBe($customer->id);

        $convertedQuotation = $getUseCase->execute($quotationId);
        expect($convertedQuotation->status())->toBe(QuotationStatus::Converted)
            ->and($convertedQuotation->convertedInvoiceId())->toBe($invoice->id());
    });
});
