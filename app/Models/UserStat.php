<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserStat extends Model
{
    use HasFactory;
    
    protected $fillable=['user_id','stat_id','answer','is_active'];

    public function statistic(){

        return $this->belongsTo(Stat::class,'stat_id','id');
    }
}
