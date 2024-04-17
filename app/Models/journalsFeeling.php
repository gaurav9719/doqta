<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class journalsFeeling extends Model
{
    use HasFactory;

    protected $fillable=['id','journal_id','feeling_type_id','is_active','created_at','updated_at'];

    
}
