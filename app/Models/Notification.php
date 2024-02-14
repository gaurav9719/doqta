<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;
    protected $fillable = ['id','role_id','receiver_id','sender_id','notification_type','is_read','message','status'];
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id','id');
    }
}
