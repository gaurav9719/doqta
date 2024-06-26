<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ConversationMessage extends Model
{
    use HasFactory;
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'post_id',
        'user_id',
        'message',
        'media',
        'media_thumbnail',
        'lat',
        'long',
        'address',
        'message_type',
        'replied_to_message_id',
        'is_active',
    ];
    public function deletedMessages()
    {
        return $this->hasMany(DeletedMessage::class);
    }

    public function reads()
    {
        return $this->hasMany(MessageRead::class,'message_id','id');
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function readReceipts()
    {
        return $this->hasMany(ReadReceipt::class, 'message_id');
    }


    public function reply_to()
    {
        return $this->belongsTo(Message::class, 'replied_to_message_id', 'id');
    }

    public function post(){

        return $this->belongsTo(Post::class,'post_id','id')->where('is_active',1);

    }

    public function share_user(){

        return $this->belongsTo(User::class,'user_id','id');

    }
}
