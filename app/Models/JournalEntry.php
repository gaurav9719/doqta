<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'journal_id',
        'user_id',
        'content',
        'media',
        'audio',
        'link',
        'feeling_id',
        'pain',
        'journal_on',
        'is_favorite',
        'is_active',
    ];
    // public function feeling(){

    //     return $this->hasOne(Feeling::class,'id','feeling_id');
    // }
    public function feeling()
    {
        return $this->belongsTo(Feeling::class, 'feeling_id');
    }
    public function feeling_types(){

        return $this->hasMany(journalsFeeling::class,'journal_entry_id','id');
    }

    public function symptom(){

        return $this->hasMany(journalSymptoms::class,'journal_entry_id','id');
    }

    public function topic(){
        
        return $this->hasOne(JournalTopic::class,'id','topic_id');
    }

    public function journal(){

        return $this->belongsTo(Journal::class,'journal_id','id')->where('is_active',1);
        
    }

}
