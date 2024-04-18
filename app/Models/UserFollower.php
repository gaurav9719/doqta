<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollower extends Model
{
    use HasFactory;


    public function follower()
    {
        // return $this->belongsTo(User::class, 'follower_user_id');
    }
}
