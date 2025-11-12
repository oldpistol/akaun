<?php

namespace Application\Invoice\UseCases;

use Barryvdh\DomPDF\Facade\Pdf;
use Domain\Invoice\Exceptions\InvoiceNotFoundException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

class GenerateReceiptPDFUseCase
{
    public function execute(string $invoiceUuid): Response
    {
        try {
            $invoice = InvoiceModel::with(['customer.primaryAddress.state', 'items'])
                ->where('uuid', $invoiceUuid)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new InvoiceNotFoundException;
        }

        $customer = $invoice->customer;
        $address = $customer?->primaryAddress;
        $items = $invoice->items;

        $pdf = Pdf::loadView('invoices.receipt', [
            'invoice' => $invoice,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("receipt-{$invoice->invoice_number}.pdf");
    }

    public function stream(string $invoiceUuid): Response
    {
        try {
            $invoice = InvoiceModel::with(['customer.primaryAddress.state', 'items'])
                ->where('uuid', $invoiceUuid)
                ->firstOrFail();
        } catch (ModelNotFoundException) {
            throw new InvoiceNotFoundException;
        }

        $customer = $invoice->customer;
        $address = $customer?->primaryAddress;
        $items = $invoice->items;

        $pdf = Pdf::loadView('invoices.receipt', [
            'invoice' => $invoice,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("receipt-{$invoice->invoice_number}.pdf");
    }
}
