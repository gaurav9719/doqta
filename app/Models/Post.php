<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    public function group_post(){

        return $this->belongsTo(Group::class,'group_id','id');
    }

    public function post_user(){
        
        return $this->belongsTo(User::class,'user_id','id');
    }
    protected $hidden = [
        
        'laravel_through_key'
    ];
}
