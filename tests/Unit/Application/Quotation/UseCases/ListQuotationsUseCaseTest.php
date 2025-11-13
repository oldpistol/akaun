<?php

use App\Enums\QuotationStatus;
use Application\Quotation\UseCases\ListQuotationsUseCase;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('returns all quotations when no filters provided', function () {
    $quotations = [
        Quotation::create(
            customerId: 1,
            quotationNumber: QuotationNumber::fromString('QT-2025-001'),
            issuedAt: new DateTimeImmutable('2025-01-01'),
            validUntil: new DateTimeImmutable('2025-01-31'),
        ),
        Quotation::create(
            customerId: 2,
            quotationNumber: QuotationNumber::fromString('QT-2025-002'),
            issuedAt: new DateTimeImmutable('2025-01-02'),
            validUntil: new DateTimeImmutable('2025-02-01'),
        ),
    ];

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotations) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn($quotations);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $result = $useCase->execute();

    expect($result)->toHaveCount(2)
        ->and($result[0])->toBeInstanceOf(Quotation::class)
        ->and($result[1])->toBeInstanceOf(Quotation::class);
});

it('returns empty array when no quotations exist', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn([]);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $result = $useCase->execute();

    expect($result)->toBeEmpty();
});

it('searches quotations by customer id', function () {
    $quotations = [
        Quotation::create(
            customerId: 1,
            quotationNumber: QuotationNumber::fromString('QT-2025-001'),
            issuedAt: new DateTimeImmutable('2025-01-01'),
            validUntil: new DateTimeImmutable('2025-01-31'),
        ),
    ];

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotations) {
        $mock->shouldReceive('search')
            ->with(['customer_id' => 1])
            ->once()
            ->andReturn($quotations);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $result = $useCase->search(['customer_id' => 1]);

    expect($result)->toHaveCount(1)
        ->and($result[0]->customerId())->toBe(1);
});

it('searches quotations by status', function () {
    $quotations = [
        Quotation::create(
            customerId: 1,
            quotationNumber: QuotationNumber::fromString('QT-2025-001'),
            issuedAt: new DateTimeImmutable('2025-01-01'),
            validUntil: new DateTimeImmutable('2025-01-31'),
        ),
    ];

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotations) {
        $mock->shouldReceive('search')
            ->with(['status' => QuotationStatus::Draft])
            ->once()
            ->andReturn($quotations);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $result = $useCase->search(['status' => QuotationStatus::Draft]);

    expect($result)->toHaveCount(1)
        ->and($result[0]->status())->toBe(QuotationStatus::Draft);
});

it('searches quotations by quotation number', function () {
    $quotations = [
        Quotation::create(
            customerId: 1,
            quotationNumber: QuotationNumber::fromString('QT-2025-001'),
            issuedAt: new DateTimeImmutable('2025-01-01'),
            validUntil: new DateTimeImmutable('2025-01-31'),
        ),
    ];

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotations) {
        $mock->shouldReceive('search')
            ->with(['quotation_number' => 'QT-2025-001'])
            ->once()
            ->andReturn($quotations);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $result = $useCase->search(['quotation_number' => 'QT-2025-001']);

    expect($result)->toHaveCount(1)
        ->and($result[0]->quotationNumber()->value())->toBe('QT-2025-001');
});

it('searches quotations with multiple filters', function () {
    $quotations = [
        Quotation::create(
            customerId: 1,
            quotationNumber: QuotationNumber::fromString('QT-2025-001'),
            issuedAt: new DateTimeImmutable('2025-01-01'),
            validUntil: new DateTimeImmutable('2025-01-31'),
        ),
    ];

    $filters = [
        'customer_id' => 1,
        'status' => QuotationStatus::Draft,
    ];

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotations, $filters) {
        $mock->shouldReceive('search')
            ->with($filters)
            ->once()
            ->andReturn($quotations);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $result = $useCase->search($filters);

    expect($result)->toHaveCount(1)
        ->and($result[0]->customerId())->toBe(1)
        ->and($result[0]->status())->toBe(QuotationStatus::Draft);
});

it('calls repository all method exactly once', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('all')
            ->once()
            ->andReturn([]);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $useCase->execute();
});

it('calls repository search method exactly once', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('search')
            ->once()
            ->andReturn([]);
    });

    $useCase = new ListQuotationsUseCase($repositoryMock);
    $useCase->search(['customer_id' => 1]);
});
