<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LateFeeSetting extends Model
{
    protected $fillable = [

        'grace_days',

        'daily_late_fee',

        'max_late_fee',

        'apply_sundays',

        'apply_holidays',

        'status',

        'created_by',

        'updated_by',
    ];

    // =========================================
    // RELACIONES
    // =========================================

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
}
