<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    use HasFactory;

    function plan_details(){

        return $this->belongsTo(Plan::class, 'plan_id', 'id');
        
    }

    
}
