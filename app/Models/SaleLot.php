<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleLot extends Model
{
    protected $fillable = [
        'sale_id',
        'lot_id',
        'sale_price',
        'is_primary',
    ];

    protected $casts = [
        'sale_price' => 'decimal:2',
        'is_primary' => 'boolean',
    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }
}
