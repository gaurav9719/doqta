<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiMessageFeedback extends Model
{
    use HasFactory;

    protected $fillable=['id','user_id','message_id','reaction','feedback'];
}
