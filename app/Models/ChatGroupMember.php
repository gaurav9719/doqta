<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatGroupMember extends Model
{
    use HasFactory;

    protected $fillable=['id','group_id','user_id','joined_at','created_at','updated_at'];

    // Define the relationship to User (member of the group)
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Define the relationship to Group (group the user belongs to)
    public function group()
    {
        return $this->belongsTo(ChatGroup::class, 'group_id');
    }

    
}
