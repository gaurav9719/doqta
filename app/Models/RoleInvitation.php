<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoleInvitation extends Model
{
    use HasFactory;


    protected $fillable=['community_id','user_id','inviter_id','role','accepted'];
}
