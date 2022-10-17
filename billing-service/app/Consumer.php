<?php

namespace App;

use App\Enums\TransactionType;
use App\Models\Task;
use App\Models\Transaction;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;

class Consumer
{
    public static function consume(): void
    {
        $consumer = Kafka::createConsumer()
            ->subscribe([
                'users-stream'
            ])
            ->withHandler(function (KafkaConsumerMessage $message) {
                $body = $message->getBody();

                switch ($body['event_name']) {
                    case 'UserCreated':
                        $user = new User($body['data']);
                        $user->password = Str::random(32);
                        $user->save();
                        break;

                    case 'TaskCreated':
                        $transactions = DB::beginTransaction(function () use ($body) {
                            $transaction = new Transaction();
                            $transaction->description = 'Task Created';

                            $transaction->user_id = $body['data']['user_id'];
                            $transaction->task_id = $body['data']['public_id'];
                            $transaction->public_id = (string) Str::uuid();

                            $transaction->generateWithdrawalValue();

                            $transaction->save();

                            $user = User::where('public_id', $transaction->public_id)->first();
                            $user->balance += $transaction->value;
                            $user->save();

                            return collect([$transaction]);
                        });

                        break;
                    case 'TasksReassigned':
                        $transactions = DB::beginTransaction(function () {
                            $tasks = Task::all();

                            $transactions = collect();

                            $tasks->each(function ($task, $transactions) {
                                $transaction = new Transaction();
                                $transaction->description = 'Task Reassigned';

                                $transaction->user_id = $task->user_id;
                                $transaction->task_id = $task->public_id;
                                $transaction->public_id = (string) Str::uuid();

                                $transaction->generateWithdrawalValue();

                                $transaction->save();

                                $user = User::where('public_id', $transaction->public_id)->first();
                                $user->balance += $transaction->value;
                                $user->save();

                                $transactions->push($transaction);
                            });

                            return $transactions;
                        });

                        break;
                    case 'TaskCompleted':
                        $transactions = DB::beginTransaction(function () use ($body) {
                            $task = Task::where('public_id', $body['data']['public_id'])->first();

                            $transaction = new Transaction();
                            $transaction->description = 'Task Completed';

                            $transaction->user_id = $task->user_id;
                            $transaction->task_id = $task->public_id;
                            $transaction->public_id = (string) Str::uuid();

                            $transaction->generateDepositValue();

                            $transaction->save();

                            $user = User::where('public_id', $transaction->public_id)->first();
                            $user->balance += $transaction->value;
                            $user->save();

                            return collect([$transaction]);
                        });

                        break;
                    default:
                        Log::info('Undefined message from broker');
                }

                if (isset($transactions)) {
                    $transactions->each(function ($transaction) {
                        $event = [
                            'event_name' => 'TransactionCreated',
                            'event_version' => 1,
                            'event_id' => (string) Str::uuid(),
                            'data' => [
                                'public_id' => $transaction->public_id,
                                'value' => $transaction->value,
                                'type' => $transaction->type,
                                'description' => $transaction->description,
                                'user_id' => $transaction->user_id,
                                'task_id' => $transaction->task_id,
                            ],
                        ];

                        if (!SchemaRegistry::validateEvent($event, 'transactions.created', 1)) {
                            throw new Exception('Event Schema Validation Failed');
                        }

                        Producer::call($event, 'transactions-stream');
                    });
                }
            })
            ->build();

        $consumer->consume();
    }
}
