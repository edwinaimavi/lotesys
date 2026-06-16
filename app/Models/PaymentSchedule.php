<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentSchedule extends Model
{
    protected $fillable = [

        'sale_id',

        'schedule_type',

        'installment_number',

        'due_date',

        'installment_amount',

        'capital',

        'interest',

        'late_fee',

        'total_amount',

        'remaining_balance',

        'status',

        'created_by',

        'updated_by',

    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
