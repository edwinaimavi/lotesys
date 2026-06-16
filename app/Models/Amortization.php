<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Amortization extends Model
{
    protected $fillable = [

        'sale_id',
        'payment_id',
        'date',
        'amount',
        'discount_amount',
        'recalculation_type',
        'reduced_installments',
        'new_installment',
        'observation',
        'created_by',
        'updated_by'

    ];

    // =========================================================
    // RELACIONES
    // =========================================================

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function updater()
    {
        return $this->belongsTo(
            User::class,
            'updated_by'
        );
    }

    public function discounts()
    {
        return $this->hasMany(SaleDiscount::class);
    }
}
