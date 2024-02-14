<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MyTeam extends Model
{
    use HasFactory;

    protected $fillable = [ ];
    public function team(){
        
        return $this->belongsTo(User::class,'member_id','id');
    }
}
