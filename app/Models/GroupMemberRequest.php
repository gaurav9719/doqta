<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GroupMemberRequest extends Model
{
    use HasFactory;

    

    public function myGroup(){
        return $this->belongsTo(Group::class,'group_id','id');
    }

    public function requested_user(){

        return $this->belongsTo(User::class,'user_id','id');
    }
    public function group(){
        return $this->belongsTo(Group::class,'group_id','id');
    }



}
