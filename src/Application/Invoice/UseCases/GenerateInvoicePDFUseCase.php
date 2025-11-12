<?php

namespace Application\Invoice\UseCases;

use Barryvdh\DomPDF\Facade\Pdf;
use Domain\Customer\ValueObjects\Uuid;
use Domain\Invoice\Repositories\InvoiceRepositoryInterface;
use Illuminate\Http\Response;

class GenerateInvoicePDFUseCase
{
    public function __construct(
        private readonly InvoiceRepositoryInterface $invoiceRepository,
    ) {}

    public function execute(string $invoiceUuid): Response
    {
        $invoice = $this->invoiceRepository->findByUuid(Uuid::fromString($invoiceUuid));

        $invoiceModel = $invoice->toPersistence();
        $invoiceModel->load(['customer.primaryAddress.state', 'items']);

        $customer = $invoiceModel->customer;
        $address = $customer?->primaryAddress;
        $items = $invoiceModel->items;

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoiceModel,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("invoice-{$invoiceModel->invoice_number}.pdf");
    }

    public function stream(string $invoiceUuid): Response
    {
        $invoice = $this->invoiceRepository->findByUuid(Uuid::fromString($invoiceUuid));

        $invoiceModel = $invoice->toPersistence();
        $invoiceModel->load(['customer.primaryAddress.state', 'items']);

        $customer = $invoiceModel->customer;
        $address = $customer?->primaryAddress;
        $items = $invoiceModel->items;

        $pdf = Pdf::loadView('invoices.pdf', [
            'invoice' => $invoiceModel,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("invoice-{$invoiceModel->invoice_number}.pdf");
    }
}
