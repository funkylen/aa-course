<?php

namespace App;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Junges\Kafka\Contracts\KafkaConsumerMessage;
use Junges\Kafka\Facades\Kafka;

class Consumer
{
    /**
     * TODO: Забирать информацию о всех транзакциях из биллинга
     */
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
                    default:
                        Log::info('Undefined message from broker');
                }
            })
            ->build();

        $consumer->consume();
    }
}
