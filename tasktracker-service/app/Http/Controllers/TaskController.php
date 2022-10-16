<?php

namespace App\Http\Controllers;

use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

class TaskController extends Controller
{
    public function index(): Response
    {
        return response(Task::all());
    }

    public function getCurrentUserTasks(): Response
    {
        $tasks = Task::where('user_id', Auth::id())->get();
        return response($tasks);
    }

    public function create(): Response
    {
        // TODO: Page create task
        return response(null);
    }

    public function store(StoreTaskRequest $request): Response
    {
        // TODO: validate manager and admin access

        $task = new Task();

        $task->fill($request->validated());

        $task->user_id = $this->getRandomUserId();

        $task->status = TaskStatus::COMPLETED;

        $task->save();

        // TODO: produce event TaskCreated

        return response($task, 201);
    }

    public function show(Task $task): Response
    {
        return response($task);
    }

    public function complete(Task $task): Response
    {
        // TODO: validate user_id for task

        $task->status = TaskStatus::COMPLETED;

        $task->save();

        // TODO: Produce event TaskCompleted

        return response($task);
    }

    public function reassignAll(): Response
    {
        // TODO: validate admin or manager

        $tasks = Task::all();

        $tasks->each(function (Task $task) {
            $task->user_id = $this->getRandomUserId();
            $task->save();
        });

        // TODO: Produce event TaskReassigned

        return response()->noContent();
    }

    private function getRandomUserId(): string
    {
        return User::inRandomOrder()->first()->public_id;
    }
}
