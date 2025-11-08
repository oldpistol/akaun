<?php

namespace Infrastructure\State\Persistence\Eloquent;

use Database\Factories\StateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StateModel extends Model
{
    /** @use HasFactory<\Database\Factories\StateFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $table = 'states';

    protected $fillable = [
        'code',
        'name',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): StateFactory
    {
        return StateFactory::new();
    }
}
