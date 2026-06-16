<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\Project;

class Block extends Model
{
    protected $table = 'blocks';

    protected $fillable = [

        'project_id',

        'name',

        'description',

        'status',

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

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function lots()
    {
        return $this->hasMany(Lot::class);
    }
}
