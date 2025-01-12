<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Post extends Model
{
    use HasFactory;

    protected $fillable=['id','parent_id','title','content','media_url','link','post_type','post_category','group_id','like_count','comment_count','is_high_confidence','share_count','repost_count','is_active'];
    public function group_post(){

        return $this->belongsTo(Group::class,'group_id','id');
    }

   

    public function post_user(){
        
        return $this->belongsTo(User::class,'user_id','id')->where('is_active',1);
    }

    public function user(){
        
        return $this->belongsTo(User::class,'user_id','id')->where('is_active',1);
    }
    public function parent_post(){
        
        return $this->belongsTo(Post::class,'parent_id','id');
    }

    public function group(){
        
        return $this->belongsTo(Group::class,'group_id','id')->where('is_active',1);
    }

    public function total_likes(){

        return $this->hasMany(PostLike::class,'post_id','id');

    }
    
    public function comment(){

        return $this->hasMany(Comment::class,'post_id','id');

    }
    public function total_comment(){

        return $this->hasMany(Comment::class,'post_id','id');
    }
    public function blockedUsers()
    {
        return $this->hasMany(BlockedUser::class, 'user_id', 'user_id');
    }

    // Users that have blocked this user
    public function blockedBy()
    {
        return $this->hasMany(BlockedUser::class, 'blocked_user_id', 'user_id');
    }
    public function reportPosts()
    {
        return $this->hasMany(ReportPost::class, 'post_id');
    }

    public function hiddenPosts()
    {
        return $this->hasMany(HiddenPost::class, 'post_id');
    }
    protected $hidden = [
        
        'laravel_through_key'
    ];
}
