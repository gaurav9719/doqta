<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    use HasFactory;

    protected $fillable=['id','inbox_id','sender_id','message','media','media_thumbnail','lat','long','address','message_type','replied_to_message_id','is_user1_trash','is_user2_trash','isread','message_read_time','is_active'];

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id','id');
    }

    public function reply_to()
    {
        return $this->belongsTo(Message::class, 'replied_to_message_id', 'id');
    }


}
