<?php

use App\Enums\QuotationStatus;
use Application\Quotation\DTOs\CreateQuotationDTO;
use Application\Quotation\DTOs\CreateQuotationItemDTO;
use Application\Quotation\UseCases\CreateQuotationUseCase;
use DateTimeImmutable;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('creates a quotation with required fields only', function () {
    $dto = new CreateQuotationDTO(
        customerId: 1,
        quotationNumber: 'QT-2025-001',
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $quotation) {
                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $quotation = $useCase->execute($dto);

    expect($quotation)->toBeInstanceOf(Quotation::class)
        ->and($quotation->customerId())->toBe(1)
        ->and($quotation->quotationNumber()->value())->toBe('QT-2025-001')
        ->and($quotation->issuedAt()->format('Y-m-d'))->toBe('2025-01-01')
        ->and($quotation->validUntil()->format('Y-m-d'))->toBe('2025-01-31')
        ->and($quotation->status())->toBe(QuotationStatus::Draft)
        ->and($quotation->notes())->toBeNull()
        ->and($quotation->termsAndConditions())->toBeNull()
        ->and($quotation->discountPercentage()->value())->toBe('0.00')
        ->and($quotation->items())->toBeEmpty();
});

it('creates a quotation with all optional fields', function () {
    $dto = new CreateQuotationDTO(
        customerId: 1,
        quotationNumber: 'QT-2025-002',
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
        notes: 'Important client',
        termsAndConditions: 'Payment within 30 days',
        discountPercentage: '10.00',
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $quotation) {
                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $quotation = $useCase->execute($dto);

    expect($quotation)->toBeInstanceOf(Quotation::class)
        ->and($quotation->customerId())->toBe(1)
        ->and($quotation->quotationNumber()->value())->toBe('QT-2025-002')
        ->and($quotation->notes())->toBe('Important client')
        ->and($quotation->termsAndConditions())->toBe('Payment within 30 days')
        ->and($quotation->discountPercentage()->value())->toBe('10.00');
});

it('creates a quotation with items', function () {
    $items = [
        new CreateQuotationItemDTO(
            description: 'Product A',
            quantity: 2,
            unitPrice: '100.00',
            taxRate: '6.00',
        ),
        new CreateQuotationItemDTO(
            description: 'Product B',
            quantity: 1,
            unitPrice: '200.00',
            taxRate: '6.00',
        ),
    ];

    $dto = new CreateQuotationDTO(
        customerId: 1,
        quotationNumber: 'QT-2025-003',
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
        items: $items,
    );

    $saveCallCount = 0;
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use (&$saveCallCount) {
        $mock->shouldReceive('save')
            ->twice()
            ->andReturnUsing(function (Quotation $quotation) use (&$saveCallCount) {
                $saveCallCount++;
                // First call: return quotation with ID set
                if ($saveCallCount === 1) {
                    $reflection = new ReflectionClass($quotation);
                    $idProperty = $reflection->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($quotation, 1);
                }

                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $quotation = $useCase->execute($dto);

    expect($quotation->items())->toHaveCount(2)
        ->and($quotation->items()[0]->description())->toBe('Product A')
        ->and($quotation->items()[0]->quantity())->toBe(2)
        ->and($quotation->items()[0]->unitPrice()->amount())->toBe('100.00')
        ->and($quotation->items()[0]->taxRate()->value())->toBe('6.00')
        ->and($quotation->items()[1]->description())->toBe('Product B')
        ->and($quotation->items()[1]->quantity())->toBe(1)
        ->and($quotation->items()[1]->unitPrice()->amount())->toBe('200.00');
});

it('creates a quotation with discount and items', function () {
    $items = [
        new CreateQuotationItemDTO(
            description: 'Service A',
            quantity: 1,
            unitPrice: '1000.00',
            taxRate: '0.00',
        ),
    ];

    $dto = new CreateQuotationDTO(
        customerId: 1,
        quotationNumber: 'QT-2025-004',
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
        discountPercentage: '15.00',
        items: $items,
    );

    $saveCallCount = 0;
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use (&$saveCallCount) {
        $mock->shouldReceive('save')
            ->twice()
            ->andReturnUsing(function (Quotation $quotation) use (&$saveCallCount) {
                $saveCallCount++;
                // First call: return quotation with ID set
                if ($saveCallCount === 1) {
                    $reflection = new ReflectionClass($quotation);
                    $idProperty = $reflection->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($quotation, 1);
                }

                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $quotation = $useCase->execute($dto);

    expect($quotation->discountPercentage()->value())->toBe('15.00')
        ->and($quotation->items())->toHaveCount(1)
        ->and($quotation->subtotal()->amount())->toBe('1000.00')
        ->and($quotation->total()->amount())->toBe('850.00');
});

it('creates quotation from DTO array', function () {
    $data = [
        'customer_id' => 1,
        'quotation_number' => 'QT-2025-005',
        'issued_at' => '2025-01-01',
        'valid_until' => '2025-01-31',
        'notes' => 'Test quotation',
        'discount_percentage' => '5.00',
    ];

    $dto = CreateQuotationDTO::fromArray($data);

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $quotation) {
                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $quotation = $useCase->execute($dto);

    expect($quotation->customerId())->toBe(1)
        ->and($quotation->quotationNumber()->value())->toBe('QT-2025-005')
        ->and($quotation->notes())->toBe('Test quotation')
        ->and($quotation->discountPercentage()->value())->toBe('5.00');
});

it('calls repository save method when creating quotation', function () {
    $dto = new CreateQuotationDTO(
        customerId: 1,
        quotationNumber: 'QT-2025-006',
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $quotation) {
                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $useCase->execute($dto);
});

it('calls repository save method twice when creating quotation with items', function () {
    $items = [
        new CreateQuotationItemDTO(
            description: 'Item',
            quantity: 1,
            unitPrice: '50.00',
        ),
    ];

    $dto = new CreateQuotationDTO(
        customerId: 1,
        quotationNumber: 'QT-2025-007',
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
        items: $items,
    );

    $saveCallCount = 0;
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use (&$saveCallCount) {
        $mock->shouldReceive('save')
            ->twice()
            ->andReturnUsing(function (Quotation $quotation) use (&$saveCallCount) {
                $saveCallCount++;
                // First call: return quotation with ID set
                if ($saveCallCount === 1) {
                    $reflection = new ReflectionClass($quotation);
                    $idProperty = $reflection->getProperty('id');
                    $idProperty->setAccessible(true);
                    $idProperty->setValue($quotation, 1);
                }

                return $quotation;
            });
    });

    $useCase = new CreateQuotationUseCase($repositoryMock);
    $useCase->execute($dto);
});
