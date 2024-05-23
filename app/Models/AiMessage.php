<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMessage extends Model
{
    use HasFactory;

    protected $fillable     =   ['id','inbox_id','sender_id','message','media','message_type','is_user1_trash','is_user2_trash','is_active'];
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id','id');
    }
}
