<?php

namespace Application\Quotation\UseCases;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Infrastructure\Quotation\Persistence\Eloquent\QuotationModel;

class GenerateQuotationPDFUseCase
{
    public function execute(string $quotationUuid): Response
    {
        $quotation = QuotationModel::with(['customer.primaryAddress.state', 'items'])
            ->where('uuid', $quotationUuid)
            ->firstOrFail();

        $customer = $quotation->customer;
        $address = $customer?->primaryAddress;
        $items = $quotation->items;

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->download("quotation-{$quotation->quotation_number}.pdf");
    }

    public function stream(string $quotationUuid): Response
    {
        $quotation = QuotationModel::with(['customer.primaryAddress.state', 'items'])
            ->where('uuid', $quotationUuid)
            ->firstOrFail();

        $customer = $quotation->customer;
        $address = $customer?->primaryAddress;
        $items = $quotation->items;

        $pdf = Pdf::loadView('quotations.pdf', [
            'quotation' => $quotation,
            'customer' => $customer,
            'address' => $address,
            'items' => $items,
        ]);

        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("quotation-{$quotation->quotation_number}.pdf");
    }
}
