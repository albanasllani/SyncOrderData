<?php declare(strict_types=1);

namespace SyncOrderData\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use SyncOrderData\MessageQueue\Message\OrderWebhookMessage;
use SyncOrderData\Service\WebhookService;

class OrderShippedSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    protected MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private WebhookService $webhookService;
    private EntityRepository $orderRepository;

    public function __construct(
        SystemConfigService $systemConfigService,
        MessageBusInterface $messageBus,
        LoggerInterface     $logger,
        WebhookService      $webhookService,
        EntityRepository    $orderRepository,

    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->webhookService = $webhookService;
        $this->orderRepository = $orderRepository;


    }

    public static function getSubscribedEvents(): array
    {
        return [
            'state_enter.order_delivery.state.shipped' => 'onOrderShipped'
        ];
    }

    /**
     * @throws ExceptionInterface
     */
    public function onOrderShipped(OrderStateMachineStateChangeEvent $event): void
    {
        $subscriberActive = $this->systemConfigService->getInt('SyncOrderData.config.ddropsSubscriberStatus');

        if ($subscriberActive !== 1) {
            return;
        }

        $orderId = $event->getOrder()->getId();
        $this->messageBus->dispatch(new OrderWebhookMessage($orderId));

    }

}
