<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Bank extends Model
{
    protected $fillable = [

        'bank_name',

        'currency',

        'account_number',

        'cci',

        'account_holder',

        'description',

        'status',

        'created_by',

        'updated_by'

    ];

    // =====================================================
    // RELACIONES
    // =====================================================

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