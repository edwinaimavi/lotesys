<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Company;

class Project extends Model
{
    protected $table = 'projects';

    protected $fillable = [

        'company_id',

        'name',

        'code',

        'description',

        'address',

        'district',

        'province',

        'department',

        'total_area',

        'registry_number',

        'start_date',

        'status',

        'created_by',

        'updated_by'

    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function blocks()
    {
        return $this->hasMany(Block::class);
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }
}
