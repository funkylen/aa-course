<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'user_id',
        'public_id',

        'type',
        'value',

        'description',
    ];

    protected $casts = [
        'type' => TransactionType::class,
    ];
}
