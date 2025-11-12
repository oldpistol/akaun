<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Filament\Resources\Invoices\InvoiceResource;
use Filament\Resources\Pages\CreateRecord;
use Infrastructure\Invoice\Repositories\EloquentInvoiceRepository;

class CreateInvoice extends CreateRecord
{
    protected static string $resource = InvoiceResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate invoice number if not provided
        if (empty($data['invoice_number'])) {
            $repository = app(EloquentInvoiceRepository::class);
            $data['invoice_number'] = $repository->nextInvoiceNumber();
        }

        return $data;
    }
}
