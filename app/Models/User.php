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
        'laravel_through_key'
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

    public function userPreferences(){

        return $this->hasOne(UserPreference::class, 'user_id', 'id');
        
    }


    public function User_interest(){

        return $this->hasMany(UsersInterest::class,'user_id', 'id');
    }

    public function user_documents(){

        return $this->hasMany(UserDocuments::class,'user_id', 'id');
    }
    public function user_roles(){
        
        return $this->hasMany(UserRole::class, 'user_id', 'id');
    }

    
    public function statistics(){

        return $this->hasMany(UserStat::class, 'user_id','id');
    }


    public function portfolio(){
        
        return $this->hasMany(UserPortfolio::class,'user_id','id');
    }


    public function SelectRecruitmentType(){
        
        return $this->hasMany(UserRecruitmentChoice::class,'user_id','id');
    }

    public function pointHistories(){
        
        return $this->hasMany(PointHistory::class,'user_id','id');
    }


    

}

