<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointSystem extends Model
{
    use HasFactory;
    
    protected $fillable=['task','point','user_role'];
}
