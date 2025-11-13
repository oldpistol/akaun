<?php

use Application\Quotation\UseCases\GetQuotationUseCase;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('retrieves a quotation by id', function () {
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
    });

    $useCase = new GetQuotationUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeInstanceOf(Quotation::class)
        ->and($result->quotationNumber()->value())->toBe('QT-2025-001')
        ->and($result->customerId())->toBe(1);
});

it('retrieves a quotation by uuid', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $uuid = (string) $quotation->uuid();

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findByUuid')
            ->once()
            ->andReturn($quotation);
    });

    $useCase = new GetQuotationUseCase($repositoryMock);
    $result = $useCase->executeByUuid($uuid);

    expect($result)->toBeInstanceOf(Quotation::class)
        ->and($result->quotationNumber()->value())->toBe('QT-2025-001');
});

it('throws exception when quotation not found by id', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);
    });

    $useCase = new GetQuotationUseCase($repositoryMock);
    $useCase->execute(99999);
})->throws(QuotationNotFoundException::class);

it('throws exception when quotation not found by uuid', function () {
    $uuid = (string) Uuid::generate();

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findByUuid')
            ->once()
            ->andReturn(null);
    });

    $useCase = new GetQuotationUseCase($repositoryMock);
    $useCase->executeByUuid($uuid);
})->throws(QuotationNotFoundException::class);
