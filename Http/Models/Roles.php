<?php

namespace App\Http\Models;

use Illuminate\Database\Eloquent\Model;

class Roles extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $table = "table_roles";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'meta_title', 'created_at', 'updated_at'
    ];
}
