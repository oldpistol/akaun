<?php

namespace App\Filament\Resources\Customers\Customers\Pages;

use App\Filament\Resources\Customers\Customers\CustomerResource;
use Filament\Resources\Pages\CreateRecord;

class CreateCustomer extends CreateRecord
{
    protected static string $resource = CustomerResource::class;
}
