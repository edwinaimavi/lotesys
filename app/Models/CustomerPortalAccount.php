<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerPortalAccount extends Model
{
    protected $fillable = ['customer_id', 'password', 'must_change_password', 'last_login_at', 'is_active'];
    protected $hidden = ['password'];
    protected $casts = ['must_change_password' => 'boolean', 'is_active' => 'boolean', 'last_login_at' => 'datetime'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public static function customersForDni(string $dni)
    {
        return Customer::where('document_type', 'DNI')->whereRaw('TRIM(document_number) = ?', [$dni]);
    }
}
