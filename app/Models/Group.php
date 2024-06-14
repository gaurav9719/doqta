<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Group extends Model
{
    use HasFactory;
    protected $fillable = ['id','name','description','cover_photo','created_by','visibility','approval_required','post_count','member_count','is_active','created_at','updated_at'];

    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    public function groupMember(){

        return $this->hasMany(GroupMember::class, 'group_id','id');
    }

    public function groupOwner(){

        return $this->belongsTo(User::class,'created_by','id');
    }

    public function member(){

        return $this->hasMany(GroupMember::class, 'group_id','id');
    }




}
