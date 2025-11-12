<?php

namespace Application\Invoice\UseCases;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

class GenerateInvoicePDFUseCase
{
    public function execute(string $invoiceUuid): Response
    {
        $invoice = InvoiceModel::with(['customer.primaryAddress.state', 'items'])
            ->where('uuid', $invoiceUuid)
            ->firstOrFail();

        $customer = $invoice->customer;
        $address = $customer?->primaryAddress;
        $items = $invoice->items;

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoice->invoice_number}.pdf");
    }

    public function stream(string $invoiceUuid): Response
    {
        $invoice = InvoiceModel::with(['customer.primaryAddress.state', 'items'])
            ->where('uuid', $invoiceUuid)
            ->firstOrFail();

        $customer = $invoice->customer;
        $address = $customer?->primaryAddress;
        $items = $invoice->items;

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoice,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$invoice->invoice_number}.pdf");
    }
}
