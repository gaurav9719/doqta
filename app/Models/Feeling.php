<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Feeling extends Model
{
    use HasFactory;

 
    public function feeling_type(){
        
        return $this->HasMany(FeelingType::class,'feeling_id','id');
    }
}
