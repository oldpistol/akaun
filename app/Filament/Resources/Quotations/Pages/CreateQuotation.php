<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Filament\Resources\Quotations\QuotationResource;
use Filament\Resources\Pages\CreateRecord;
use Infrastructure\Quotation\Repositories\EloquentQuotationRepository;

class CreateQuotation extends CreateRecord
{
    protected static string $resource = QuotationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-generate quotation number if not provided
        if (empty($data['quotation_number'])) {
            $repository = app(EloquentQuotationRepository::class);
            $data['quotation_number'] = $repository->nextQuotationNumber();
        }

        // Ensure discount_rate has a default value
        if (! isset($data['discount_rate'])) {
            $data['discount_rate'] = 0;
        }

        return $data;
    }
}
