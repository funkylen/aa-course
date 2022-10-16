<?php

namespace App\Models;

use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'public_id',
        'user_id',
        'price',
        'status',
    ];

    public function generateWithdrawalValue()
    {
        if ($this->value) {
            return;
        }

        $this->value = mt_rand(-10, -20);
        $this->type = TransactionType::WITHDRAWAL;
    }

    public function generateDepositValue()
    {
        if ($this->value) {
            return;
        }

        $this->value = mt_rand(20, 40);
        $this->type = TransactionType::DEPOSIT;
    }
}
