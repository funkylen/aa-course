<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();

        if (!in_array($user->role, [Role::ACCOUNTANT, Role::ADMIN])) {
            return response([
                'current_balance' => $user->balance,
                'log' => Transaction::where('user_id', $user->public_id)->all(),
            ]);
        }

        $query = Transaction::on();

        $date = $request->get('filter[date]');

        if ($date) {
            $query->where('created_at', $date);
        } else {
            $query->where('created_at', Carbon::today());
        }

        $transactions = $query->get();

        return response([
            'earned_money' => -1 * $transactions->sum(function ($transaction) {
                return (float) $transaction->value;
            }),
            'log' => $transactions,
        ]);
    }
}
