<?php

use Application\Invoice\UseCases\GenerateInvoicePDFUseCase;
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
});
