<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    protected $fillable = [

        'customer_id',
        'lot_id',
        'sale_type',

        'sale_code',
        'sale_date',

        'lot_price',
        'initial_payment',
        'balance_finance',

        'installments_count',

        'payment_mode',
        'custom_payment',
        
        'monthly_payment',
        'interest_rate',

        'first_payment_date',
        'payment_day',

        'late_fee_setting_id',

        'status',

        'created_by',
        'updated_by'
    ];

    // =====================================================
    // RELACIONES
    // =====================================================

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function paymentSchedules()
    {
        return $this->hasMany(PaymentSchedule::class);
    }

    public function discounts()
    {
        return $this->hasMany(SaleDiscount::class);
    }

    public function lateFeeSetting()
    {
        return $this->belongsTo(LateFeeSetting::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function rescission()
    {
        return $this->hasOne(Rescission::class);
    }

    public function payments()
    {
        return $this->hasMany(\App\Models\Payment::class);
    }
}
