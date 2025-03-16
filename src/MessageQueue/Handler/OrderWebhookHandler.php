<?php declare(strict_types=1);

namespace SyncOrderData\MessageQueue\Handler;

use SyncOrderData\MessageQueue\Message\OrderWebhookMessage;
use SyncOrderData\Service\WebhookService;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Psr\Log\LoggerInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Context;

#[AsMessageHandler]
class OrderWebhookHandler
{
    private WebhookService $webhookService;
    private EntityRepository $orderRepository;
    private LoggerInterface $logger;

    public function __construct(
        WebhookService   $webhookService,
        EntityRepository $orderRepository,
        LoggerInterface  $logger
    )
    {
        $this->webhookService = $webhookService;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
    }

    public function __invoke(OrderWebhookMessage $message): void
    {
        $orderId = $message->getOrderId();

        $criteria = new Criteria([$orderId]);
//        $criteria->addAssociations([
//            'transactions.stateMachineState',
//            'deliveries.stateMachineState',
//            'stateMachineState',
//            'transactions.paymentMethod',
//            'deliveries.shippingMethod',
//            'lineItems',
//            'currency',
//            'orderCustomer',
//            'billingAddress',
//            'deliveries.shippingOrderAddress'
//        ]);

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
            'billingAddress.country',
            'billingAddress.countryState',
            'deliveries.shippingOrderAddress',
            'deliveries.shippingOrderAddress.country',
            'deliveries.shippingOrderAddress.countryState'

        ]);

        $order = $this->orderRepository->search($criteria, Context::createDefaultContext())->first();

        if (!$order) {
            $this->logger->error(sprintf('Order with ID %s not found.', $orderId));
            return;
        }
        $this->webhookService->sendWebhook($order);
    }
}
