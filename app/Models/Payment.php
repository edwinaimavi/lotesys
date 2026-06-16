<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{

    protected $fillable = [

        'sale_id',
        'payment_schedule_id',
        'payment_type',
        'payment_date',
        'amount',
        'late_fee_paid',
        'discount',
        'observation',
        'payment_method',
        'bank_id',
        'operation_number',
        'user_id',
        'status',
        'created_by',
        'updated_by',

    ];

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function paymentSchedule()
    {
        return $this->belongsTo(
            PaymentSchedule::class,
            'payment_schedule_id'
        );
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

    public function details()
    {
        return $this->hasMany(
            PaymentDetail::class,
            'payment_id'
        );
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class)
            ->orderByRaw("
            CASE
                WHEN document_type = 'invoice' THEN 1
                WHEN document_type = 'receipt' THEN 2
                WHEN document_type = 'sale_note' THEN 3
                ELSE 4
            END
        ");
    }
}
