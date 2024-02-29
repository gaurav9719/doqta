<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyRoster extends Model
{
    use HasFactory;
    protected $fillable = ['id','user_id','roster_id','recruiter_id','my_team_member_id'];

    public function roster(){

        return $this->belongsTo(User::class,'roster_id','id');
    }

    public function member(){

        return $this->belongsTo(User::class,'roster_id','id');
    }
}
