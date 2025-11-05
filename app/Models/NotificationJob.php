<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NotificationJob extends Model
{
    use HasFactory;
    protected $table = "notification_jobs";

    protected $fillable = [
        'channel',
        'recipient',
        'message',
        'status',
        'attempts',
        'max_attempts',
        'next_run_at',
        'idempotency_key',
        'last_error',
        'processed_at'
    ];

    protected $dates = [
        'next_run_at',
        'processed_at',
        'created_at',
        'updated_at'
    ];
}
