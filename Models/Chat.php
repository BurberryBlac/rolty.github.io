<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    protected $table = "chats";
    
    protected $fillable = [
        'sender_id', 'user_id', 'sender_name', 'receiver_id', 'receiver_name', 'message'
    ];
}
