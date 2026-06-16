<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $table = 'companies';

    protected $fillable = [

        'business_name',
        'trade_name',
        'ruc',
        'address',
        'phone',
        'email',
        'status',
         'created_by',
        'updated_by',

    ];


    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
