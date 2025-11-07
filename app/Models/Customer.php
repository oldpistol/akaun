<?php

namespace App\Models;

use App\CustomerType;
use App\RiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Customer extends Model
{
    /** @use HasFactory<\Database\Factories\CustomerFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'name',
        'email',
        'phone_primary',
        'phone_secondary',
        'nric',
        'passport_no',
        'company_ssm_no',
        'gst_number',
        'customer_type',
        'is_active',
        'billing_attention',
        'credit_limit',
        'risk_level',
        'notes',
        'email_verified_at',
    ];

    public function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'credit_limit' => 'decimal:2',
            'email_verified_at' => 'datetime',
            'customer_type' => CustomerType::class,
            'risk_level' => RiskLevel::class,
        ];
    }

    public function addresses(): MorphMany
    {
        return $this->morphMany(Address::class, 'addressable');
    }

    public function primaryAddress(): MorphOne
    {
        return $this->morphOne(Address::class, 'addressable')->where('is_primary', true);
    }

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::saved(function (self $model): void {
            $model->ensureSinglePrimaryAddress();
        });
    }

    public function ensureSinglePrimaryAddress(): void
    {
        $primaryCount = $this->addresses()->where('is_primary', true)->count();

        if ($primaryCount === 0) {
            $first = $this->addresses()->first();
            if ($first) {
                $first->forceFill(['is_primary' => true])->save();
            }

            return;
        }

        if ($primaryCount > 1) {
            $firstPrimary = $this->addresses()->where('is_primary', true)->orderBy('id')->first();
            $this->addresses()->where('is_primary', true)->whereKeyNot($firstPrimary?->getKey())->update(['is_primary' => false]);
        }
    }
}
