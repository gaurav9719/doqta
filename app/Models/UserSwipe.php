<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserSwipe extends Model
{
    use HasFactory;
    protected $fillable = ['id','swiping_user_id','swiped_user_id','swipe_type','role_id','is_active'];
}
