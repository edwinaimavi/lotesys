<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Project;
use App\Models\Block;

class Lot extends Model
{
    protected $table = 'lots';

    protected $fillable = [

        'project_id',

        'block_id',

        'code',

        'number',

        'area',

        'unit_measure',

        'cash_price',

        'financed_price',


        'north_boundary',
        'south_boundary',
        'east_boundary',
        'west_boundary',

        'status',

        'observation',

        'created_by',

        'updated_by'

    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function block()
    {
        return $this->belongsTo(Block::class);
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
