<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleDiscount extends Model
{
    protected $fillable = [

        'sale_id',
        'amortization_id',
        'amount',
        'reason',
        'created_by',
        'updated_by',

    ];

    /**
     * Venta
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Amortización relacionada
     */
    public function amortization()
    {
        return $this->belongsTo(Amortization::class);
    }

    /**
     * Usuario creador
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Usuario editor
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    
}
