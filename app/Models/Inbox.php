<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inbox extends Model
{
    use HasFactory;
    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function receiver()
    {
        return $this->belongsTo(User::class, 'receiver_id');
    }

    // Define a scope for filtering based on user's first name
    public function scopeSearchByFirstName($query, $search)
    {
        return $query->whereHas('sender', function ($query) use ($search) {
            $query->where('first_name', 'LIKE', '%' . $search . '%');
        });
    }

    // Define a scope for filtering threads where the user is either sender or receiver
    public function scopeWhereUserIsSenderOrReceiver($query, $userId)
    {
        return $query->where('sender_id', $userId)->orWhere('receiver_id', $userId);
    }

}
