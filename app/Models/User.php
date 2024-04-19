<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Passport\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'laravel_through_key',
        'otp',
        'otp_expiry_time',
        'device_token',
        'email_verified_at'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];



    public function posts()
    {
        return $this->hasManyThrough(Post::class, GroupMember::class, 'user_id', 'group_id', 'id', 'group_id');
    }

    // return $this->hasManyThrough(
    //     Post::class,       // Related model that you want to access
    //     GroupMember::class, // Intermediate model
    //     'user_id',          // Foreign key on the intermediate model (GroupMember) that references the local model (User)
    //     'group_id',         // Foreign key on the related model (Post) that references the intermediate model (GroupMember)
    //     'id',               // Local key on the local model (User) used for the relationship
    //     'group_id'          // Local key on the intermediate model (GroupMember) used for the relationship
    // );

    public function checkGroup()
    {
        return $this->hasOneThrough(Group::class, GroupMember::class, 'user_id', 'id', 'id', 'group_id');
    }

    public function userPost()
    {
        return $this->hasMany(Post::class,'user_id','id');
    }

    public function supporter()
    {
        return $this->hasMany(UserFollower::class,'user_id','id');
    }

    public function supporting()
    {
        return $this->hasMany(UserFollower::class,'follower_user_id','id');
    }


    public function userParticipant(){

        return $this->hasMany(UserParticipantCategory::class,'user_id','id');
    }
    public function user_activities(){

        return $this->hasMany(ActivityLog::class,'user_id','id');
    }
    
    public function user_follow(){

        return $this->belongsTo(UserFollower::class,'id','user_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_followers', 'user_id', 'follower_user_id');
    }


    public function user_group(){

        return $this->hasMany(GroupMember::class,'user_id','id');

    }


    public function user_interest(){

        return $this->hasMany(UsersInterest::class,'user_id', 'id');
    }

    public function user_documents(){

        return $this->hasMany(UserDocuments::class,'user_id', 'id');
    }
   

    
  
    public function portfolio(){
        
        return $this->hasMany(UserPortfolio::class,'user_id','id');
    }




    

}

