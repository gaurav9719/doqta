<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChatInsightEntry extends Model
{
    use HasFactory;

    protected $fillable= [
        'report_id',
        'insight_id',
        'entry_id',
    ];
}
