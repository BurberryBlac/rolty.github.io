<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Follower extends Model
{

    protected $table = "table_followers";

    protected $fillable = [
        'user_id', 'follow_id', 'type', 'status'
    ];

    public function followers()
    {
        return $this->hasMany(User::class,'id','follow_id');
    }
}
