<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserParticipantCategory extends Model
{
    use HasFactory;

    protected $fillable=['id','user_id','participant_id','is_active'];
}
