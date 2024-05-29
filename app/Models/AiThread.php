<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiThread extends Model
{
    use HasFactory;

    protected $fillable=['id','message_id','sender_id','receiver_id','thread_name','is_user1_trash','is_user2_trash','is_active','created_at','updated_at'];
}
