<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserFollower extends Model
{
    use HasFactory;


    public function follower()
    {
        return $this->belongsTo(User::class, 'follower_user_id');
    }

    // Define the relationship with the User model for the user being followed
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship with the User model for the other user (followed or following)
    public function other_user()
    {
        return $this->belongsTo(User::class, 'other_user_id');
    }
}
