<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserMedicalCredentials extends Model
{
    use HasFactory;

    protected $fillable= ['id','user_id','medicial_degree_type','specialty','medicial_document','verified_status','is_active'];

    public function medical_certificate(){

        return $this->belongsTo(MedicalCredential::class,'medicial_degree_type','id');

    }

    public function speciality(){

        return $this->belongsTo(Specialty::class,'specialty','id');
        
    }
}
