<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserPlan extends Model
{
    use HasFactory;

    protected $fillable = [
        'transaction_id', 
        'original_transaction_id', 
        'user_id', 
        'plan_id', 
        'start_date', 
        'expiry_date', 
        'last_update',
        'is_trial_plan',
        'purchased_device',
        'cancelled_period_end',
        'cancelled_period_at_end',
        'cancelled_at',
        'payment_status',
        'is_active',
        'created_at',
        'updated_at'
    ];
    function plan_details(){

        return $this->belongsTo(Plan::class, 'plan_id', 'id');
        
    }

    
}
