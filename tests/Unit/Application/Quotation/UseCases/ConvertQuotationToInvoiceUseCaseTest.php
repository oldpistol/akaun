<?php

use Application\Quotation\UseCases\ConvertQuotationToInvoiceUseCase;
use Domain\Quotation\Exceptions\QuotationNotFoundException;
use Domain\Quotation\Repositories\QuotationRepositoryInterface;
use Mockery\MockInterface;

use function Pest\Laravel\mock;

// Note: ConvertQuotationToInvoiceUseCase depends on CreateInvoiceUseCase which is final
// and cannot be easily mocked. The comprehensive tests for this use case are in the
// end-to-end tests: tests/Feature/QuotationUseCasesEndToEndTest.php

it('throws exception when converting non-existent quotation', function () {
    $quotationRepositoryMock = mock(QuotationRepositoryInterface::class, function (MockInterface $mock) {
        $mock->shouldReceive('findById')
            ->with(99999)
            ->once()
            ->andReturn(null);
    });

    $invoiceRepositoryMock = mock(\Domain\Invoice\Repositories\InvoiceRepositoryInterface::class);

    // Create a mock instance of CreateInvoiceUseCase
    $createInvoiceUseCase = new \Application\Invoice\UseCases\CreateInvoiceUseCase($invoiceRepositoryMock);

    $useCase = new ConvertQuotationToInvoiceUseCase(
        $quotationRepositoryMock,
        $invoiceRepositoryMock,
        $createInvoiceUseCase
    );

    $useCase->execute(99999);
})->throws(QuotationNotFoundException::class);

it('is tested comprehensively in end-to-end tests', function () {
    expect(true)->toBeTrue();
})->todo('ConvertQuotationToInvoiceUseCase conversion logic is tested in Feature tests');
