<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationOff extends Model
{
    /**
     * The attributes that should be connect with table.
     *
     * @var array
     */
    protected $table = "notification_off";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'type', 'status'
    ];
}
