<?php

use App\Enums\QuotationStatus;
use Application\Quotation\UseCases\AcceptQuotationUseCase;
use Domain\Quotation\Entities\Quotation;
use Domain\Quotation\Exceptions\QuotationAlreadyAcceptedException;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Domain\Quotation\ValueObjects\QuotationNumber;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

it('accepts a draft quotation', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable,
        validUntil: (new DateTimeImmutable)->modify('+30 days'),
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

    $useCase = new AcceptQuotationUseCase($repositoryMock);
    $acceptedQuotation = $useCase->execute(1);

    expect($acceptedQuotation->status())->toBe(QuotationStatus::Accepted)
        ->and($acceptedQuotation->acceptedAt())->not->toBeNull();
});

it('throws exception when accepting non-existent quotation', function () {
    $repositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);
    });

    $useCase = new AcceptQuotationUseCase($repositoryMock);
    $useCase->execute(99999);
})->throws(QuotationNotFoundException::class);

it('throws exception when accepting already accepted quotation', function () {
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

    $useCase = new AcceptQuotationUseCase($repositoryMock);
    $useCase->execute(1);
})->throws(QuotationAlreadyAcceptedException::class);

it('calls repository save method exactly once', function () {
    $quotation = Quotation::create(
        customerId: 1,
        quotationNumber: QuotationNumber::fromString('QT-2025-001'),
        issuedAt: new DateTimeImmutable,
        validUntil: (new DateTimeImmutable)->modify('+30 days'),
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

    $useCase = new AcceptQuotationUseCase($repositoryMock);
    $useCase->execute(1);
});
