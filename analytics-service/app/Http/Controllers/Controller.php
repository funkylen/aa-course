<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Task;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    public function validateAdmin()
    {
        return Auth::user()?->role == Role::ADMIN;
    }

    public function earnedToday()
    {
        $this->validateAdmin();

        // TODO: Реализовать метод получение информации о том, сколько заработано за сегодня
    }

    public function usersGoneNegative()
    {
        $this->validateAdmin();

        // TODO: Реализовать метод получение информации о том, сколько попугов ушло в минус сегодня
    }

    public function mostExpensiveTask()
    {
        $this->validateAdmin();

        // TODO: Реализовать метод получения инфы о самой дорогой задаче за день/неделю/месяц
    }
}
