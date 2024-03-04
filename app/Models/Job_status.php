<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Job_status extends Model
{
    use HasFactory;
    protected $fillable = ['id','user_id', 'job_id', 'is_running'];

}
