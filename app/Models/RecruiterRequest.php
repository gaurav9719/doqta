<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecruiterRequest extends Model
{
    use HasFactory;

    protected $fillable = ['id','user_id','recruiter_id','request_status','is_active','request_on'];

    public function requested_user(){
        
        return $this->belongsTo(User::class, 'user_id','id');
    }
}
