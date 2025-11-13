<?php

use App\Enums\QuotationStatus;
use Application\Quotation\UseCases\DeclineQuotationUseCase;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationAlreadyAcceptedException;
use Domain\Quotation\Exceptions\QuotationAlreadyDeclinedException;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('declines a draft quotation', function () {
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

    $useCase = new DeclineQuotationUseCase($repositoryMock);
    $declinedQuotation = $useCase->execute(1);

    expect($declinedQuotation->status())->toBe(QuotationStatus::Declined)
        ->and($declinedQuotation->declinedAt())->not->toBeNull();
});

it('throws exception when declining non-existent quotation', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);
    });

    $useCase = new DeclineQuotationUseCase($repositoryMock);
    $useCase->execute(99999);
})->throws(QuotationNotFoundException::class);

it('throws exception when declining already accepted quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable,
        validUntil: (new DateTimeImmutable)->modify('+30 days'),
    );

    $quotation->accept();

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);
    });

    $useCase = new DeclineQuotationUseCase($repositoryMock);
    $useCase->execute(1);
})->throws(QuotationAlreadyAcceptedException::class);

it('throws exception when declining already declined quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable('2025-01-01'),
        validUntil: new DateTimeImmutable('2025-01-31'),
    );

    $quotation->decline();

    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) use ($quotation) {
        $mock->shouldReceive('findById')
            ->with(1)
            ->once()
            ->andReturn($quotation);
    });

    $useCase = new DeclineQuotationUseCase($repositoryMock);
    $useCase->execute(1);
})->throws(QuotationAlreadyDeclinedException::class);

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

    $useCase = new DeclineQuotationUseCase($repositoryMock);
    $useCase->execute(1);
});
