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


}
