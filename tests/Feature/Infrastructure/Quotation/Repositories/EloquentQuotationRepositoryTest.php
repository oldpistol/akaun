<?php

use App\Enums\QuotationStatus;
use Domain\Customer\ValueObjects\Money;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Invoice\ValueObjects\TaxRate;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Entities\QuotationItem;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;
use Infrastructure\Quotation\Repositories\EloquentQuotationRepository;

uses(RefreshDatabase::class);
uses()->group('repository', 'integration');

beforeEach(function () {
    $this->repository = new EloquentQuotationRepository;
});

describe('findById', function () {
    it('finds quotation by id with items', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $quotationModel = QuotationModel::factory()->make(['customer_id' => $customer->id]);
        $quotationModel->saveQuietly();
        $quotationModel->items()->create([
            'description' => 'Service A',
            'quantity' => 2,
            'unit_price' => '100.00',
            'tax_rate' => '10.00',
        ]);

        $result = $this->repository->findById($quotationModel->id);

        expect($result)
            ->toBeInstanceOf(Quotation::class)
            ->id()->toBe($quotationModel->id)
            ->quotationNumber()->value()->toBe($quotationModel->quotation_number)
            ->items()->toHaveCount(1);
    });

    it('returns null when quotation not found', function () {
        $result = $this->repository->findById(99999);

        expect($result)->toBeNull();
    });
});

describe('findByUuid', function () {
    it('finds quotation by UUID', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $quotationModel = QuotationModel::factory()->create(['customer_id' => $customer->id]);

        $result = $this->repository->findByUuid(Uuid::fromString($quotationModel->uuid));

        expect($result)
            ->toBeInstanceOf(Quotation::class)
            ->uuid()->value()->toBe($quotationModel->uuid);
    });

    it('returns null when UUID not found', function () {
        $result = $this->repository->findByUuid(Uuid::generate());

        expect($result)->toBeNull();
    });
});

describe('findByQuotationNumber', function () {
    it('finds quotation by quotation number', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $quotationModel = QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-202511-0001',
        ]);

        $result = $this->repository->findByQuotationNumber(QuotationNumber::fromString('QUO-202511-0001'));

        expect($result)
            ->toBeInstanceOf(Quotation::class)
            ->quotationNumber()->value()->toBe('QUO-202511-0001');
    });

    it('returns null when quotation number not found', function () {
        $result = $this->repository->findByQuotationNumber(QuotationNumber::fromString('QUO-999999-9999'));

        expect($result)->toBeNull();
    });
});

describe('findByCustomerId', function () {
    it('finds all quotations for a customer', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        QuotationModel::factory(3)->create(['customer_id' => $customer->id]);
        QuotationModel::factory(2)->create(); // Different customer

        $result = $this->repository->findByCustomerId($customer->id);

        expect($result)->toBeArray()
            ->toHaveCount(3)
            ->each->toBeInstanceOf(Quotation::class);
    });

    it('returns empty array when customer has no quotations', function () {
        $result = $this->repository->findByCustomerId(99999);

        expect($result)->toBeArray()->toBeEmpty();
    });
});

describe('save', function () {
    it('saves new quotation', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();

        $quotation = Quotation::create(
            customerId: $customer->id,
            quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
            issuedAt: new DateTimeImmutable('2025-11-01'),
            validUntil: new DateTimeImmutable('2025-12-01'),
        );

        $saved = $this->repository->save($quotation);

        expect($saved->id())->not->toBeNull()
            ->and(QuotationModel::count())->toBe(1);
    });

    it('saves quotation with items', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();

        $quotation = Quotation::create(
            customerId: $customer->id,
            quotationNumber: QuotationNumber::fromString('QUO-202511-0001'),
            issuedAt: new DateTimeImmutable('2025-11-01'),
            validUntil: new DateTimeImmutable('2025-12-01'),
        );

        $item = QuotationItem::create(
            quotationId: 0,
            description: 'Service',
            quantity: 2,
            unitPrice: Money::fromAmount('100.00'),
            taxRate: TaxRate::fromPercentage('10'),
        );

        $quotation->addItem($item);
        $saved = $this->repository->save($quotation);

        expect($saved->items())->toHaveCount(1)
            ->and($saved->items()[0]->description())->toBe('Service');
    });

    it('updates existing quotation', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $quotationModel = QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'notes' => 'Original notes',
        ]);

        $quotation = $this->repository->findById($quotationModel->id);
        $quotation->updateNotes('Updated notes');
        $saved = $this->repository->save($quotation);

        expect($saved->notes())->toBe('Updated notes')
            ->and(QuotationModel::count())->toBe(1);
    });
});

describe('search', function () {
    it('searches quotations by customer id', function () {
        $customer1 = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $customer2 = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        QuotationModel::factory(2)->create(['customer_id' => $customer1->id]);
        QuotationModel::factory()->create(['customer_id' => $customer2->id]);

        $result = $this->repository->search(['customer_id' => $customer1->id]);

        expect($result)->toHaveCount(2);
    });

    it('searches quotations by status', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        QuotationModel::factory(2)->create([
            'customer_id' => $customer->id,
            'status' => QuotationStatus::Draft,
        ]);
        QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'status' => QuotationStatus::Sent,
        ]);

        $result = $this->repository->search(['status' => QuotationStatus::Draft]);

        expect($result)->toHaveCount(2);
    });

    it('searches quotations by quotation number pattern', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-202511-0001',
        ]);
        QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-202511-0002',
        ]);
        QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'quotation_number' => 'QUO-202512-0001',
        ]);

        $result = $this->repository->search(['quotation_number' => '202511']);

        expect($result)->toHaveCount(2);
    });
});

describe('nextQuotationNumber', function () {
    it('generates first quotation number for current month', function () {
        $expected = 'QUO-'.date('Ym').'-0001';

        $result = $this->repository->nextQuotationNumber();

        expect($result)->toBe($expected);
    });

    it('generates incremented quotation number', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $yearMonth = date('Ym');
        QuotationModel::factory()->create([
            'customer_id' => $customer->id,
            'quotation_number' => "QUO-{$yearMonth}-0001",
        ]);

        $result = $this->repository->nextQuotationNumber();

        expect($result)->toBe("QUO-{$yearMonth}-0002");
    });
});

describe('delete', function () {
    it('soft deletes quotation', function () {
        $customer = \Infrastructure\Customer\Persistence\Eloquent\CustomerModel::factory()->create();
        $quotationModel = QuotationModel::factory()->create(['customer_id' => $customer->id]);
        $quotation = $this->repository->findById($quotationModel->id);

        $result = $this->repository->delete($quotation);

        expect($result)->toBeTrue()
            ->and(QuotationModel::count())->toBe(0)
            ->and(QuotationModel::withTrashed()->count())->toBe(1);
    });
});
