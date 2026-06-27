<?php

namespace App\Models;

use Carbon\Carbon;
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

    public function getEffectiveDueDate(): Carbon
    {
        $dueDate = Carbon::parse($this->due_date)->startOfDay();

        $sale = $this->sale;

        if (
            $sale &&
            $sale->is_legacy_sale &&
            $sale->collection_rules_start_date
        ) {
            $rulesStartDate = Carbon::parse(
                $sale->collection_rules_start_date
            )->startOfDay();

            return $rulesStartDate->gt($dueDate)
                ? $rulesStartDate
                : $dueDate;
        }

        return $dueDate;
    }

    public function isOverdueForCollections(?Carbon $date = null): bool
    {
        $date = $date ?: Carbon::today();

        return $this->getEffectiveDueDate()->lt($date->copy()->startOfDay());
    }
}
