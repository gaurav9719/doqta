<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMember extends Model
{
    use HasFactory;

    public function communities(){
        
        return $this->belongsTo(Group::class, 'group_id','id');
    }


    public function communities_post(){
            
        return $this->belongsTo(Post::class, 'group_id','group_id');
    }


    public function groupUser(){

        return $this->belongsTo(User::class, 'user_id','id');

    }
    public function groupUsern(){

        return $this->hasOne(User::class);

    }


    public function groupPost(){

        return $this->hasMany(Post::class,'id','post_id');
    }

    public function group(){

        return $this->belongsTo(Group::class,'group_id','id');
    }

    public function HealthProvider(){

        return $this->hasMany(UserParticipantCategory::class,'user_id','user_id');
    }
    public function user(){

        return $this->belongsTo(User::class, 'user_id','id');

    }

    






}
