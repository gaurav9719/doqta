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


    


    

}
