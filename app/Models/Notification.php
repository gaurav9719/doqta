<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    public function sender(){
        
        return $this->belongsTo(User::class, 'sender_id','id');
    }

    public function user(){
        
        return $this->belongsTo(User::class, 'receiver_id','id');
    }

}
