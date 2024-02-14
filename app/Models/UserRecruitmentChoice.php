<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserRecruitmentChoice extends Model
{
    use HasFactory;
    protected $fillable = ['id','user_id','role_id','recruiter_type','is_active'] ;
}

