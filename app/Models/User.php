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
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];



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

