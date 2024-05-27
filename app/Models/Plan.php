<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Plan extends Model
{
    use HasFactory;

    protected $fillable = ['id',
        'type', 'name', 'price', 'currency', 'currency_symbol', 'duration', 'is_active','created_at','updated_at'
    ];

    function features(){
        
        return $this->hasMany(PlanFeature::class, 'plan_id', 'id');
    }
}
