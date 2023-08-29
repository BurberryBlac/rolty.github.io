<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Feed extends Model
{
    protected $table = "table_feed";

    public function getUserDetails()
    {
        return $this->hasOne('App\Models\User','id','user_id');
    }
}
