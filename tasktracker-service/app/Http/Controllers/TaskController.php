<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Enums\TaskStatus;
use App\Http\Requests\StoreTaskRequest;
use App\Models\Task;
use App\Models\User;
use App\Producer;
use App\SchemaRegistry;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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

    public function create(Request $request): Response
    {
        // TODO: Page create task
        return response(null);
    }

    private function validateUserHasRoles(...$roles)
    {
        $user = Auth::user();

        if (!collect($user->roles)->hasAny(...$roles)) {
            abort(403);
        }
    }

    public function store(StoreTaskRequest $request): Response
    {
        $this->validateUserHasRoles(Role::MANAGER, Role::ADMIN);

        $task = new Task();

        $task->fill($request->validated());

        $task->user_id = $this->getRandomUserId();

        $task->status = TaskStatus::COMPLETED;

        if (str($task->title)->containsAll(['[', ']'])) {
            abort(400, "Remove Jira ID from title.");
        }

        $task->save();

        $event = [
            'event_id' => (string) Str::uuid(),
            'event_version' => 2,
            'event_name' => 'TaskCreated',
            'data' => [
                'user_id' => $task->user_id,
                'status' => $task->status,
                'title' => $task->title,
                'jira_id' => $task->jira_id,
                'description' => $task->description,
            ],
        ];

        if (!SchemaRegistry::validateEvent($event, 'tasks.created', $event['event_version'])) {
            throw new Exception('Event Schema Validation Failed');
        }

        Producer::call($event, 'tasks-stream');

        return response($task, 201);
    }

    public function show(Task $task): Response
    {
        return response($task);
    }

    public function complete(Task $task): Response
    {
        $user = Auth::user();

        if ($user->id !== $task->user_id) {
            abort(403);
        }

        $task->status = TaskStatus::COMPLETED;

        $task->save();

        $event = [
            'event_id' => (string) Str::uuid(),
            'event_version' => 1,
            'event_name' => 'TaskUpdated',
            'data' => [
                'user_id' => $task->user_id,
                'status' => $task->status,
                'title' => $task->title,
                'description' => $task->description,
            ],
        ];

        Producer::call($event, 'tasks-stream');

        if (!SchemaRegistry::validateEvent($event, 'tasks.updated', $event['event_version'])) {
            throw new Exception('Event Schema Validation Failed');
        }

        $eventBL = [
            'event_id' => (string) Str::uuid(),
            'event_version' => 1,
            'event_name' => 'TaskCompleted',
            'data' => [
                'user_id' => $task->user_id,
                'status' => $task->status,
                'title' => $task->title,
            ],
        ];

        if (!SchemaRegistry::validateEvent($eventBL, 'tasks.created', $eventBL['event_version'])) {
            throw new Exception('Event Schema Validation Failed');
        }

        Producer::call($eventBL, 'tasks-bl');

        return response($task);
    }

    public function reassignAll(): Response
    {
        $this->validateUserHasRoles(Role::MANAGER, Role::ADMIN);

        $tasks = Task::all();

        $tasks->each(function (Task $task) {
            $task->user_id = $this->getRandomUserId();

            $event = [
                'event_id' => (string) Str::uuid(),
                'event_version' => 1,
                'event_name' => 'TaskUpdated',
                'data' => [
                    'user_id' => $task->user_id,
                    'status' => $task->status,
                    'title' => $task->title,
                    'description' => $task->description,
                ],
            ];

            if (!SchemaRegistry::validateEvent($event, 'tasks.updated', $event['event_version'])) {
                throw new Exception('Event Schema Validation Failed');
            }

            Producer::call($event, 'tasks-stream');

            $task->save();
        });

        $eventBL = [
            'event_id' => (string) Str::uuid(),
            'event_version' => 1,
            'event_name' => 'TasksReassigned',
            'data' => [
                'reassigner_public_id' => Auth::id(),
            ],
        ];

        if (!SchemaRegistry::validateEvent($eventBL, 'tasks.updated', $eventBL['event_version'])) {
            throw new Exception('Event Schema Validation Failed');
        }

        Producer::call($eventBL, 'tasks-bl');

        return response()->noContent();
    }

    private function getRandomUserId(): string
    {
        return User::inRandomOrder()->first()->public_id;
    }
}
