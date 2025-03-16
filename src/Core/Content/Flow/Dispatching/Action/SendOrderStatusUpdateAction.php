<?php declare(strict_types=1);

namespace SyncOrderData\Core\Content\Flow\Dispatching\Action;

use Dotdigital\Flow\Core\Framework\Event\SendOrderStatusUpdateAware;
use Shopware\Core\Content\Flow\Dispatching\Action\FlowAction;
use Shopware\Core\Content\Flow\Dispatching\StorableFlow;
use Shopware\Core\Framework\Event\OrderAware;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use SyncOrderData\MessageQueue\Message\OrderWebhookMessage;
use SyncOrderData\Service\WebhookService;

class SendOrderStatusUpdateAction extends FlowAction implements EventSubscriberInterface

{
    private WebhookService $webhookService;
    private EntityRepository $orderRepository;

    private SystemConfigService $systemConfigService;
    protected MessageBusInterface $messageBus;

    public function __construct(
        WebhookService      $webhookService,
        EntityRepository    $orderRepository,
        SystemConfigService $systemConfigService,
        MessageBusInterface $messageBus,

    )
    {
        $this->webhookService = $webhookService;
        $this->orderRepository = $orderRepository;
        $this->systemConfigService = $systemConfigService;
        $this->messageBus = $messageBus;

    }

    public static function getSubscribedEvents(): array
    {
        return [
            self::getName() => 'handle',
        ];
    }

    public static function getName(): string
    {
        return 'action.send_order_status_update';
    }

    public function requirements(): array
    {
        return [SendOrderStatusUpdateAware::class, OrderAware::class];
    }

    public function handleFlow(StorableFlow $flow): void
    {

        $subscriberActive = $this->systemConfigService->getInt('SyncOrderData.config.ddropsSubscriberStatus');

        if ($subscriberActive === 0) {

            if (!$flow->hasStore(OrderAware::ORDER_ID)) {
                return;
            }
            $orderId = $flow->getStore(OrderAware::ORDER_ID);

            $this->messageBus->dispatch(new OrderWebhookMessage($orderId));
        }


    }
}