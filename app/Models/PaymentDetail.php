<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentDetail extends Model
{
    protected $fillable = [

        'payment_id',
        'payment_schedule_id',
        'applied_amount',
        'created_by',
        'updated_by'

    ];

    /**
     * RELACIÓN: PAGO
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * RELACIÓN: CRONOGRAMA
     */
    public function paymentSchedule()
    {
        return $this->belongsTo(PaymentSchedule::class);
    }

    /**
     * RELACIÓN: USUARIO CREADOR
     */
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * RELACIÓN: USUARIO EDITOR
     */
    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    
}
