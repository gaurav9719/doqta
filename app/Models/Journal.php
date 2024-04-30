<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Journal extends Model
{
    use HasFactory;

    protected $fillable = ['id','user_id','title','topic_id','writing_for','color','entry_date','is_favorite','is_active'];

    public function color(){

        return $this->hasOne(Color::class,'id','color');
    }

    public function topic(){

        return $this->hasOne(JournalTopic::class,'id','topic_id');
    }
}
