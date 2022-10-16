<?php

namespace App;

use Junges\Kafka\Facades\Kafka;
use Junges\Kafka\Message\Message;

class Producer
{
    public static function call(array $event, string $topic): void
    {
        $message = new Message(body: $event);

        $producer = Kafka::publishOn($topic)
            ->withMessage($message);

        $producer->send();
    }
}
