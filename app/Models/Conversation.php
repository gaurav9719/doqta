<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'creator_id',
        'is_active',
        'is_group',
        'message_id',
    ];

    public function participants()
    {
        return $this->hasMany(Participant::class);
    }

    public function messages()
    {
        return $this->hasMany(ParticipantMessage::class);
    }

 
    public function lastMessage()
    {
        return $this->hasOne(ParticipantMessage::class)->latest();
    }
   
}
