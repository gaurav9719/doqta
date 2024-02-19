<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Stat extends Model
{
    use HasFactory;


    public function userStats(){

        return $this->hasOne(UserStat::class,"stat_id","id");
    }

   
}
