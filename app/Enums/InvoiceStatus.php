<?php

namespace App\Enums;

enum InvoiceStatus: string
{
    case Draft = 'Draft';
    case Sent = 'Sent';
    case Paid = 'Paid';
    case Overdue = 'Overdue';
    case Cancelled = 'Cancelled';
    case Void = 'Void';
}
