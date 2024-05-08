<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PostLike extends Model
{
    use HasFactory;
    protected $fillable = [
        'id',
        'user_id',
        'post_id',
        'comment_id',
        'reaction',
        'is_active',
        'created_at',
        'updated_at'

    ];
}
