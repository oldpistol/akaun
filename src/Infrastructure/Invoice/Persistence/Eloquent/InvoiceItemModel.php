<?php

namespace Infrastructure\Invoice\Persistence\Eloquent;

use Database\Factories\InvoiceItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceItemModel extends Model
{
    /** @use HasFactory<\Database\Factories\InvoiceItemFactory> */
    use HasFactory;

    protected $table = 'invoice_items';

    protected $fillable = [
        'invoice_id',
        'description',
        'quantity',
        'unit_price',
        'tax_rate',
    ];

    public function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'tax_rate' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<InvoiceModel, covariant self>
     */
    public function invoice(): BelongsTo
    {
        return $this->belongsTo(InvoiceModel::class);
    }

    protected static function newFactory(): InvoiceItemFactory
    {
        return InvoiceItemFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(function (self $model): void {
            if ($model->invoice) {
                $model->invoice->recalculateTotals();
            }
        });

        static::deleted(function (self $model): void {
            if ($model->invoice) {
                $model->invoice->recalculateTotals();
            }
        });
    }
}
