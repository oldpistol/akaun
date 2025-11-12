<?php

use Application\Invoice\UseCases\GenerateInvoicePDFUseCase;
use Application\Invoice\UseCases\GenerateReceiptPDFUseCase;
use Application\Quotation\UseCases\GenerateQuotationPDFUseCase;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/invoices/{uuid}/pdf', function (string $uuid, GenerateInvoicePDFUseCase $useCase) {
        return $useCase->execute($uuid);
    })->name('invoices.pdf.download');

    Route::get('/invoices/{uuid}/pdf/view', function (string $uuid, GenerateInvoicePDFUseCase $useCase) {
        return $useCase->stream($uuid);
    })->name('invoices.pdf.view');

    Route::get('/invoices/{uuid}/receipt', function (string $uuid, GenerateReceiptPDFUseCase $useCase) {
        return $useCase->execute($uuid);
    })->name('invoices.receipt.download');

    Route::get('/invoices/{uuid}/receipt/view', function (string $uuid, GenerateReceiptPDFUseCase $useCase) {
        return $useCase->stream($uuid);
    })->name('invoices.receipt.view');

    Route::get('/quotations/{uuid}/pdf', function (string $uuid, GenerateQuotationPDFUseCase $useCase) {
        return $useCase->execute($uuid);
    })->name('quotations.pdf.download');

    Route::get('/quotations/{uuid}/pdf/view', function (string $uuid, GenerateQuotationPDFUseCase $useCase) {
        return $useCase->stream($uuid);
    })->name('quotations.pdf.view');
});
