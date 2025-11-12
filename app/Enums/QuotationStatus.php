<?php

namespace App\Enums;

enum QuotationStatus: string
{
    case Draft = 'Draft';
    case Sent = 'Sent';
    case Accepted = 'Accepted';
    case Declined = 'Declined';
    case Expired = 'Expired';
    case Converted = 'Converted';
}
