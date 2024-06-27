<?php

namespace App\Models;

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    public function conversation_participants()
    {
        return $this->hasMany(Participant::class,'conversation_id','id');
    }

    public function participants()
    {
        return $this->belongsToMany(User::class, 'participants')
            ->withPivot('status');
    }


    public function messages()
    {
        return $this->hasMany(ParticipantMessage::class);
    }

 
    public function lastMessage()
    {
        return $this->hasOne(ParticipantMessage::class)->latest();
    }

    public function unreadMessagesForUser($userId)
    {
        return $this->hasMany(ParticipantMessage::class, 'conversation_id')
                    ->where('sender_id', '!=', $userId)
                    ->whereNotExists(function ($query) use ($userId) {
                        $query->select(DB::raw(1))
                              ->from('message_reads')
                              ->whereColumn('message_reads.message_id', 'participant_messages.id')
                              ->where('message_reads.user_id', $userId);
                    })
                    ->whereNotExists(function ($query) use ($userId) {
                        $query->select(DB::raw(1))
                              ->from('deleted_messages')
                              ->whereColumn('deleted_messages.message_id', 'participant_messages.id')
                              ->where('deleted_messages.user_id', $userId);
                    });
    }

    public function blocks()
    {
        return $this->hasManyThrough(BlockedUser::class, Participant::class, 'conversation_id', 'user_id', 'id', 'user_id');
    }
   
}
