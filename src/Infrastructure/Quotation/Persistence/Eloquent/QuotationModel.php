<?php

namespace Infrastructure\Quotation\Persistence\Eloquent;

use App\Enums\QuotationStatus;
use Database\Factories\QuotationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;
use Infrastructure\Invoice\Persistence\Eloquent\InvoiceModel;

class QuotationModel extends Model
{
    /** @use HasFactory<\Database\Factories\QuotationFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'quotations';

    protected $fillable = [
        'uuid',
        'customer_id',
        'quotation_number',
        'status',
        'issued_at',
        'valid_until',
        'accepted_at',
        'declined_at',
        'converted_at',
        'converted_invoice_id',
        'subtotal',
        'tax_total',
        'discount_rate',
        'discount_amount',
        'total',
        'notes',
    ];

    public function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'valid_until' => 'datetime',
            'accepted_at' => 'datetime',
            'declined_at' => 'datetime',
            'converted_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'discount_rate' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => QuotationStatus::class,
        ];
    }

    /**
     * @return BelongsTo<CustomerModel, covariant self>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerModel::class);
    }

    /**
     * @return HasMany<QuotationItemModel, covariant self>
     */
    public function items(): HasMany
    {
        return $this->hasMany(QuotationItemModel::class, 'quotation_id');
    }

    /**
     * @return BelongsTo<InvoiceModel, covariant self>
     */
    public function convertedInvoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class, 'converted_invoice_id');
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }

    public function recalculateTotals(): void
    {
        $items = $this->items()->get();

        $subtotal = 0;
        $taxTotal = 0;

        foreach ($items as $item) {
            $itemSubtotal = $item->quantity * $item->unit_price;
            $itemTax = $itemSubtotal * ($item->tax_rate / 100);

            $subtotal += $itemSubtotal;
            $taxTotal += $itemTax;
        }

        $discountAmount = $subtotal * ($this->discount_rate / 100);
        $total = $subtotal + $taxTotal - $discountAmount;

        $this->updateQuietly([
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax_total' => number_format($taxTotal, 2, '.', ''),
            'discount_amount' => number_format($discountAmount, 2, '.', ''),
            'total' => number_format($total, 2, '.', ''),
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): QuotationFactory
    {
        return QuotationFactory::new();
    }
}
