<?php

namespace Infrastructure\Quotation\Persistence\Eloquent;

use Database\Factories\QuotationItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuotationItemModel extends Model
{
    /** @use HasFactory<\Database\Factories\QuotationItemFactory> */
    use HasFactory;

    protected $table = 'quotation_items';

    protected $fillable = [
        'quotation_id',
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
     * @return BelongsTo<QuotationModel, covariant self>
     */
    public function quotation(): BelongsTo
    {
        return $this->belongsTo(QuotationModel::class);
    }

    protected static function newFactory(): QuotationItemFactory
    {
        return QuotationItemFactory::new();
    }

    protected static function booted(): void
    {
        static::saved(function (self $model): void {
            if ($model->quotation) {
                $model->quotation->recalculateTotals();
            }
        });

        static::deleted(function (self $model): void {
            if ($model->quotation) {
                $model->quotation->recalculateTotals();
            }
        });
    }
}
