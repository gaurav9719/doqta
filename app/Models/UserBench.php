<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserBench extends Model
{
    use HasFactory;

    protected $fillable = ['id','user_id','rejectd_user_id','is_active'];
}
