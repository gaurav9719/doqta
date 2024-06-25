<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ParticipantMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'sender_id',
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
}
