<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ParticipantCategory extends Model
{
    use HasFactory;

    protected $fillable=['id','name','reason','is_active','created_at','updated_at'];

    public function user_participant_category(){
        
        return $this->hasOne(UserParticipantCategory::class,'participant_id','id');
  }
}
