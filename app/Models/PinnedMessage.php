<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PinnedMessage extends Model
{
    use HasFactory;

    public function message()
    {
        return $this->belongsTo(AiMessage::class, 'message_id','id');
    }

}
