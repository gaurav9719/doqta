<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
}
