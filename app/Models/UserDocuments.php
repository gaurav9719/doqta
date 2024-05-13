<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserDocuments extends Model
{
    use HasFactory;
    protected $fillable     =   ['id','user_id','document_type','document','verified_status'];

    public function document(){

        return $this->belongsTo(DocumentTypes::class,'document_type','id');
    }

    public function document_type(){

        return $this->belongsTo(DocumentTypes::class,'document_type','id');
    }

}
