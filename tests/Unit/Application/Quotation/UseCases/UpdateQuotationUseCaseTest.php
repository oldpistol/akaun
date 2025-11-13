<?php

use Application\Quotation\DTOs\CreateQuotationItemDTO;
use Application\Quotation\DTOs\UpdateQuotationDTO;
use Application\Quotation\UseCases\UpdateQuotationUseCase;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('updates quotation notes', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(notes: 'Updated notes');
    $updatedQuotation = $useCase->execute(1, $dto);

    expect($updatedQuotation->notes())->toBe('Updated notes');
});

it('updates quotation valid until date', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(validUntil: new DateTimeImmutable('2025-02-28'));
    $updatedQuotation = $useCase->execute(1, $dto);

    expect($updatedQuotation->validUntil()->format('Y-m-d'))->toBe('2025-02-28');
});

it('updates quotation terms and conditions', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(termsAndConditions: 'Payment within 15 days');
    $updatedQuotation = $useCase->execute(1, $dto);

    expect($updatedQuotation->termsAndConditions())->toBe('Payment within 15 days');
});

it('updates quotation discount percentage', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(discountPercentage: '10.00');
    $updatedQuotation = $useCase->execute(1, $dto);

    expect($updatedQuotation->discountPercentage()->value())->toBe('10.00');
});

it('updates quotation items', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    // Set ID using reflection
    $reflection = new ReflectionClass($quotation);
    $idProperty = $reflection->getProperty('id');
    $idProperty->setAccessible(true);
    $idProperty->setValue($quotation, 1);

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $items = [
        new CreateQuotationItemDTO(
            description: 'New Item',
            quantity: 3,
            unitPrice: '150.00',
            taxRate: '6.00',
        ),
    ];

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(items: $items);
    $updatedQuotation = $useCase->execute(1, $dto);

    expect($updatedQuotation->items())->toHaveCount(1)
        ->and($updatedQuotation->items()[0]->description())->toBe('New Item');
});

it('updates multiple fields at once', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(
        notes: 'Multiple updates',
        termsAndConditions: 'New terms',
        discountPercentage: '5.00',
    );

    $updatedQuotation = $useCase->execute(1, $dto);

    expect($updatedQuotation->notes())->toBe('Multiple updates')
        ->and($updatedQuotation->termsAndConditions())->toBe('New terms')
        ->and($updatedQuotation->discountPercentage()->value())->toBe('5.00');
});

it('throws exception when updating non-existent quotation', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(notes: 'Test');

    $useCase->execute(99999, $dto);
})->throws(QuotationNotFoundException::class);

it('calls repository save method exactly once', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);

        $mock->shouldReceive('save')
            ->once()
            ->andReturnUsing(function (Quotation $q) {
                return $q;
            });
    });

    $useCase = new UpdateQuotationUseCase($repositoryMock);
    $dto = new UpdateQuotationDTO(notes: 'Test');
    $useCase->execute(1, $dto);
});
