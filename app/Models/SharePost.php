<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SharePost extends Model
{
    use HasFactory;
    protected $fillable=['post_id','user_id','message_id','send_to','is_active','created_at','updated_at'];
}
