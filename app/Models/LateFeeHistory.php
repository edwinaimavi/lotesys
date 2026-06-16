<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateFeeHistory extends Model
{
    protected $fillable = [

        'payment_schedule_id',

        'late_fee_amount',

        'late_days',

        'daily_late_fee_applied',

        'calculation_type',

        'calculated_at',

        'deleted_by',

        'deletion_reason',

        'status',

        'created_by',

        'updated_by'

    ];

    // =====================================================
    // RELACIONES
    // =====================================================

    public function paymentSchedule()
    {
        return $this->belongsTo(
            PaymentSchedule::class
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

    public function deleter()
    {
        return $this->belongsTo(
            User::class,
            'deleted_by'
        );
    }
}
