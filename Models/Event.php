<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    //
    protected $fillable = [
        'name', 'user_id', 'place', 'date', 'hours', 'mins', 'image', 'half', 'description'
    ];
    
    public function invitations()
    {
        return $this->hasMany(EventInvite::class);
    }
    
    public function getUserProfile()
    {
        return $this->hasOne('App\Models\User','id','user_id');
    }
}
