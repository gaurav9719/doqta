<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;


    public function commentUser(){

        return $this->belongsTo(User::class,'user_id','id');
    }


    public function replies(){

        return $this->hasMany(Comment::class,'parent_id','id');
    }


    public function replied_to(){

        return $this->belongsTo(User::class,'mention_user_id','id');
    }

    public function totalLikes(){

        return $this->hasMany(PostLike::class,'comment_id','id');

    }

    public function commentsLikes(){

        return $this->hasMany(CommentLike::class,'comment_id','id');

    }

    public function total_comment(){
        return $this->hasMany(comment::class,'parent_id','id');
    }
    


}
