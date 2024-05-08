<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportToComment extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $fillable = [
        'id',
        'post_id',
        'comment_id',
        'user_id',
        'reason',
        'created_at',
        'updated_at',
    ];

}
