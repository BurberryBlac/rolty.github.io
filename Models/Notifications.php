<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notifications extends Model
{
    /**
     * The attributes that should be connect with table.
     *
     * @var array
     */
    protected $table = "notifications";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'from_user_id', 'to_user_id', 'title', 'body', 'view_status', 'status', 'type', 'payload'
    ];
}
