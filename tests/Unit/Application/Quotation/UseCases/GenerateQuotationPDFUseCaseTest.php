<?php

use Application\Quotation\UseCases\GenerateQuotationPDFUseCase;

// Note: GenerateQuotationPDFUseCase works directly with Eloquent models
// and PDF generation, so we test it in Feature tests instead of Unit tests
// See: tests/Feature/QuotationPDFTest.php for PDF generation tests

it('is tested in feature tests', function () {
    expect(true)->toBeTrue();
})->todo('GenerateQuotationPDFUseCase is tested in Feature tests');
