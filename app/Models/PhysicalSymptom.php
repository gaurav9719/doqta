<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhysicalSymptom extends Model
{
    use HasFactory;


    protected $fillable = [
        'symptom',
        'user_id',
        'topic_id',
        'type',
        'is_active',
    ];
}
