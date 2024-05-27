<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SummaryLike extends Model
{
    use HasFactory;

    protected $fillable=['post_id','type','user_id','reaction'];
}
