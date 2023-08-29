<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificationReport extends Model
{
    /**
     * The attributes that should be connect with table.
     *
     * @var array
     */
    protected $table = "notifications_report";
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'notification_id', 'status'
    ];
}
