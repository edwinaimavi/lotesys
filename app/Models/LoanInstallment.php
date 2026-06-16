<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoanInstallment extends Model
{
    /** @use HasFactory<\Database\Factories\LoanInstallmentFactory> */
    use HasFactory;

  protected $fillable = [
        'loan_id',
        'installment_number',
        'due_date',
        'amount',
        'capital',
        'interest',
        'late_fee',
        'status',
        'paid_amount',
    ];

    // Relaciones
    public function loan()
    {
        return $this->belongsTo(Loan::class);
    }
}
