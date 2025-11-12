<?php

namespace Infrastructure\Invoice\Persistence\Eloquent;

use App\Enums\InvoiceStatus;
use App\Models\PaymentMethod;
use Database\Factories\InvoiceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Infrastructure\Customer\Persistence\Eloquent\CustomerModel;

class InvoiceModel extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'invoices';

    protected $fillable = [
        'uuid',
        'customer_id',
        'invoice_number',
        'status',
        'issued_at',
        'due_at',
        'paid_at',
        'payment_method_id',
        'payment_reference',
        'payment_receipt_path',
        'subtotal',
        'tax_total',
        'total',
        'notes',
    ];

    public function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'paid_at' => 'datetime',
            'subtotal' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'status' => InvoiceStatus::class,
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
     * @return BelongsTo<PaymentMethod, covariant self>
     */
    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    /**
     * @return HasMany<InvoiceItemModel, covariant self>
     */
    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItemModel::class, 'invoice_id');
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

        $this->updateQuietly([
            'subtotal' => number_format($subtotal, 2, '.', ''),
            'tax_total' => number_format($taxTotal, 2, '.', ''),
            'total' => number_format($subtotal + $taxTotal, 2, '.', ''),
        ]);
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): InvoiceFactory
    {
        return InvoiceFactory::new();
    }
}
