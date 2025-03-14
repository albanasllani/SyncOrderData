<?php declare(strict_types=1);

namespace SyncOrderData\Service;

 use Symfony\Component\Messenger\MessageBusInterface;

class OrderWebhookSender
{
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
    }

    public function sendOrderData(string $message): void
    {

        dd(" Services OrderWebhookSender sendOrderData " . $message);


    }


}