<?php declare(strict_types=1);

namespace SyncOrderData\MessageQueue\Message;

use Shopware\Core\Framework\MessageQueue\AsyncMessageInterface;

class OrderWebhookMessage implements AsyncMessageInterface
{
    private string $orderId;


    public function __construct(string $orderId)
    {
        $this->orderId = $orderId;
    }

    public function getOrderId(): string
    {
        return $this->orderId;
    }
}