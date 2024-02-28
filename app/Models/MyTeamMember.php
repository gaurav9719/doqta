<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyTeamMember extends Model
{
    use HasFactory;
    public function member(){

        return $this->belongsTo(User::class,'dater_id','id');
    }

    public function user_swipes(){

        return $this->belongsTo(UserSwipe::class,'member_id','swiping_user_id');
    }



    public function user_states(){

        return $this->hasMany(UserStat::class,'user_id', 'roster_id');
    }

    


    

}
