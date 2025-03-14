<?php declare(strict_types=1);

namespace SyncOrderData\Subscriber;

use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
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
            'state_enter.order_delivery.state.shipped' => 'onOrderShipped',

        ];
    }

    public function onOrderShipped(OrderStateMachineStateChangeEvent $event): void
    {
        $subscriberActive = $this->systemConfigService->getInt('SyncOrderData.config.ddropsSubscriberStatus');

        if ($subscriberActive != 1) {
            return;
        }

        $order = $this->fetchFullOrder($event->getOrder()->getId(), Context::createDefaultContext());

        if (!$order) {
            return;
        }
        $this->webhookService->sendWebhook($order);
    }

    private function fetchFullOrder(string $orderId, Context $context): ?OrderEntity
    {
        $criteria = new Criteria([$orderId]);
        $criteria->addAssociations([
            'transactions.stateMachineState',
            'deliveries.stateMachineState',
            'stateMachineState',
            'transactions.paymentMethod',
            'deliveries.shippingMethod',
            'lineItems',
            'currency',
            'orderCustomer',
            'billingAddress',
            'deliveries.shippingOrderAddress'
        ]);

        return $this->orderRepository->search($criteria, $context)->first();
    }

}
