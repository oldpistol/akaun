<?php

use Application\Quotation\UseCases\DeleteQuotationUseCase;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('deletes a quotation', function () {
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

        $mock->shouldReceive('delete')
            ->with($quotation)
            ->once()
            ->andReturn(true);
    });

    $useCase = new DeleteQuotationUseCase($repositoryMock);
    $result = $useCase->execute(1);

    expect($result)->toBeTrue();
});

it('throws exception when deleting non-existent quotation', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);
    });

    $useCase = new DeleteQuotationUseCase($repositoryMock);
    $useCase->execute(99999);
})->throws(QuotationNotFoundException::class);

it('calls repository delete method exactly once', function () {
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

        $mock->shouldReceive('delete')
            ->with($quotation)
            ->once()
            ->andReturn(true);
    });

    $useCase = new DeleteQuotationUseCase($repositoryMock);
    $useCase->execute(1);
});
