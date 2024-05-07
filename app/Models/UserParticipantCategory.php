<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserParticipantCategory extends Model
{
    use HasFactory;

    protected $fillable=['id','user_id','participant_id','is_active'];
   
    public function participant(){
    
        return $this->belongsTo(ParticipantCategory::class,'participant_id','id');
    }

    public function user(){

        return $this->belongsTo(User::class,'user_id','id');
    }

    public function userGroups(){
        
        return $this->hasMany(GroupMember::class,'user_id','user_id');
    }


}

