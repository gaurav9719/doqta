<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'post_id',
        'community_id',
        'action',
        'action_details',
        'ip_address',
        'device_info',
        'location',
        'is_active',
        'created_at',
        'updated_at',
        'comment_id'
    ];

    function post_details(){
        
        return $this->hasOne(Post::class, 'id', 'post_id');
    }
}
