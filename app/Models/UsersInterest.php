<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UsersInterest extends Model
{
    use HasFactory;
    protected $fillable= ['id','user_id','interest_id','is_active'];

    public function interest(){
        return $this->belongsTo(Interest::class,'interest_id','id');
    }
}
