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

        "id",
        "social_id",
        "name",
        "user_name",
        "email",
        "password",
        "dob",
        "country_code",
        "phone_no",
        "country_id",
        "state_id",
        "city_id",
        "zipcode",
        "lat",
        "long",
        "gender",
        "pronoun",
        "ethnicity",
        "guideline",
        "complete_step",
        "login_type",
        "device_type",
        "device_token",
        "profile",
        "cover",
        "bio",
        "stripe_customer_id",
        "mute_notification",
        "otp",
        "otp_expiry_time",
        "is_email_verified",
        "signup_process",
        "email_verified_at",
        "remember_token",
        "is_active",
        "created_at",
        "updated_at"




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
        'email_verified_at',
        'stripe_customer_id'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'dob' => 'date:m/d/Y',

    ];



    // public function posts()
    // {
    //     return $this->hasManyThrough(Post::class, GroupMember::class, 'user_id', 'group_id', 'id', 'group_id');
    // }


    public function posts()
    {
        return $this->hasManyThrough(Post::class, GroupMember::class, 'user_id', 'group_id', 'id', 'group_id')
            ->whereHas('post_user', function ($query) {
                
                $query->where('is_active', 1);

            });
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
        return $this->hasMany(Post::class, 'user_id', 'id')->where('is_active', 1);
    }

    public function supporter()
    {
        return $this->hasMany(UserFollower::class, 'user_id', 'id');
    }

    public function supporting()
    {
        return $this->hasMany(UserFollower::class, 'follower_user_id', 'id');
    }


    public function userParticipant()
    {

        return $this->hasMany(UserParticipantCategory::class, 'user_id', 'id');
    }
    public function user_activities()
    {

        return $this->hasMany(ActivityLog::class, 'user_id', 'id');
    }

    public function user_follow()
    {

        return $this->belongsTo(UserFollower::class, 'id', 'user_id');
    }

    public function following()
    {
        return $this->belongsToMany(User::class, 'user_followers', 'user_id', 'follower_user_id');
    }


    public function user_group()
    {

        return $this->hasMany(GroupMember::class, 'user_id', 'id');
    }

    public function user_groupLimit($limit = null)
    {
        return $this->hasMany(GroupMember::class, 'user_id', 'id')->limit(1);

       
    }





    public function user_single_group()
    {

        return $this->hasOne(GroupMember::class, 'user_id', 'id');
    }



    public function user_interest()
    {

        return $this->hasMany(UsersInterest::class, 'user_id', 'id');
    }

    public function user_documents()
    {

        return $this->hasMany(UserDocuments::class, 'user_id', 'id');
    }

    public function user_medical_certificate()
    {

        return $this->hasMany(UserMedicalCredentials::class, 'user_id', 'id');
    }

    public function blockedUsers()
    {
        return $this->hasMany(BlockedUser::class, 'user_id', 'id');
    }

    // Users that have blocked this user
    public function blockedBy()
    {
        return $this->hasMany(BlockedUser::class, 'blocked_user_id', 'id');
    }

  

   








    public function followers()
    {
        return $this->hasMany(UserFollower::class, 'user_id', 'id');
    }
    public function reportPosts()
    {
        return $this->hasMany(ReportPost::class, 'user_id');
    }

 public function hiddenPosts()
    {
        return $this->hasMany(HiddenPost::class, 'user_id');
    }

    public function messageReads()
    {
        return $this->hasMany(MessageRead::class,'user_id','id');
    }

    public function conversations()
    {
        return $this->belongsToMany(Conversation::class, 'participants')
            ->withPivot('status');
           // ->wherePivot('status', 'active');
    }


    public function portfolio()
    {

        return $this->hasMany(UserPortfolio::class, 'user_id', 'id');
    }
}
