<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class journalSymptoms extends Model
{
    use HasFactory;
    protected $fillable=['id','journal_id','symptom_id','is_active','created_at','updated_at'];
}