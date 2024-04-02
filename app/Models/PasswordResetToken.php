<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;
    protected $fillable = [
       
        'otp',
        'email',
        'token',
        'otp_expiry_time',
    ];

    protected $dates = [
        'otp_expiry_time',
    ];

    protected $primaryKey = 'email'; // Replace with your actual primary key column name
public $incrementing = false; // Specify if your primary key is auto-incrementing or not
   

}
