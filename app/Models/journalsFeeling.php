<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class journalsFeeling extends Model
{
    use HasFactory;

    protected $fillable=['id','journal_entry_id','feeling_type','is_active','created_at','updated_at'];

   

    public function feeling_type(){

        return $this->belongsTo(FeelingType::class,'feeling_type','id');
        
    }

}
