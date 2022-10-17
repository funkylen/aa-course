<?php

namespace App\Jobs;

use App\Mail\UserPayIssueMail;
use App\Models\User;
use App\Producer;
use App\SchemaRegistry;
use Exception;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class PayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $users = User::all();

        $users->each(function ($user) {
            $balance = $user->balance;

            if ($balance <= 0) {
                return;
            }

            $user->notify(new UserPayIssueMail($user->name, $balance));

            $user->balance = 0;

            $user->save();

            $event = [
                'event_name' => 'UserPayIssued',
                'event_id' => (string) Str::uuid(),
                'event_version' => 1,
                'data' => [
                    'user_id' => $user->public_id,
                ],
            ];

            if (!SchemaRegistry::validateEvent($event, 'users.pay-issued', $event['event_version'])) {
                throw new Exception('Event Schema Validation Failed');
            }

            Producer::call($event, 'users-bl');
        });

    }
}
