<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class JournalReport extends Model
{
    use HasFactory;

    protected $fillable= [
        'journal_id',
        'ai_thread_id',
        'user_id',
        'start_date',
        'end_date',
        'ids_count',
        'start_id',
        'end_id',
        'is_chat_included',
        'chat_start_id',
        'chat_end_id',
        'chat_ids_count',
        'report',
        'type',
        'report_type',
    ];
}
