<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rescission extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'rescission_date',
        'reason',
        'overdue_installments',
        'amount_paid',
        'penalty_amount',
        'observation',
        'user_id',
    ];

    protected $casts = [
        'rescission_date' => 'date',
        'amount_paid' => 'decimal:2',
        'penalty_amount' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELACIONES
    |--------------------------------------------------------------------------
    */

    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
