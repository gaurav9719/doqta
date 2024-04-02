<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocuments extends Model
{
    use HasFactory;

    public function document(){

        return $this->belongsTo(DocumentTypes::class,'document_type','id');
    }
}
