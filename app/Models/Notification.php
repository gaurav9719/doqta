<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'receiver_id',
        'sender_id',
        'notification_type',
        'is_read',
        'message',
        'status',
        'community_id',
        'post_id',
        'comment_id',
        'mention_id',
        'parent_id'
    ];





    public function sender(){
        
        return $this->belongsTo(User::class, 'sender_id','id')->where('is_active',1);
    }

    public function user(){
        
        return $this->belongsTo(User::class, 'receiver_id','id');
    }

    public function invitation(){
        
        return $this->belongsTo(RoleInvitation::class,'invitation_id','id');
    }

}
