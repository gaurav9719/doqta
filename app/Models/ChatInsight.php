<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatInsight extends Model
{
    use HasFactory;

    protected $fillable = [
        'report_id',
        'type',
        'details',
    ];
}
