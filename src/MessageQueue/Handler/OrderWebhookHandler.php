<?php declare(strict_types=1);

namespace SyncOrderData\MessageQueue\Handler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use SyncOrderData\MessageQueue\Message\OrderWebhookMessage;
use SyncOrderData\Service\OrderWebhookSender;

#[AsMessageHandler]
class OrderWebhookHandler
{
    public function __invoke(OrderWebhookMessage $message)
    {
        dd($message);
        // ... do some work - like sending an SMS message!

    }
}

