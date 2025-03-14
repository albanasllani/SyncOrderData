<?php declare(strict_types=1);

namespace SyncOrderData\Subscriber;

use DateTimeInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Shopware\Core\Checkout\Order\Event\OrderStateMachineStateChangeEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Content\Product\ProductEvents;
use Symfony\Component\Messenger\Exception\ExceptionInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use SyncOrderData\Message\OrderWebhookMessage;
use SyncOrderData\MessageQueue\Handler\OrderWebhookHandler;

class OrderShippedSubscriber implements EventSubscriberInterface
{
    private SystemConfigService $systemConfigService;
    protected MessageBusInterface $messageBus;
    private LoggerInterface $logger;
    private HttpClientInterface $httpClient;


    public function __construct(
        SystemConfigService $systemConfigService,
        MessageBusInterface $messageBus,
        LoggerInterface     $logger,
        HttpClientInterface $httpClient,

    )
    {
        $this->systemConfigService = $systemConfigService;
        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->HttpClientInterface = $httpClient;


    }

    public static function getSubscribedEvents(): array
    {
        return [
//            ProductEvents::PRODUCT_LOADED_EVENT => 'onOrderShipped'
            'state_enter.order_delivery.state.shipped' => 'onOrderShipped',

        ];
    }

    /**
     * @throws ExceptionInterface
     */
    public function onOrderShipped(OrderStateMachineStateChangeEvent $event): void
    {

        //ToDo Add Webhook call
        $subscriberActive = $this->systemConfigService->getInt('SyncOrderData.config.ddropsSubscriberStatus');
        $webhookUrl = $this->systemConfigService->getString('SyncOrderData.config.ddropsWebHookUrl');

        if (!$subscriberActive) {
            return;
        }
        $this->sendWebhook($event->getOrder());
//
//        $this->logger->info('Order shipped event triggered', ['orderId' => $event->getOrder()->getId()]);
//
//        $message = new OrderWebhookMessage($event->getOrder()->getId());
//        $this->messageBus->dispatch($message);

    }


    public function sendWebhook(OrderEntity $order): void
    {
        $webhookUrl = $this->systemConfigService->getString('SyncOrderData.config.ddropsWebHookUrl');
//        $webhookUrl = 'https://webhook.site/29032600-2274-4349-ab89-d59f843c2058';
        $payload = $this->formatOrderData($order);


//        var_dump($payload);
//        dd($payload);
        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://webhook.site/29032600-2274-4349-ab89-d59f843c2058',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode($payload),
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json'
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            echo $response;

            $this->logger->info('Webhook sent 199555778 ', ['status' => $response->getStatusCode()]);
        } catch (\Exception $e) {
            $this->logger->error('Webhook failed 666699999', ['error' => $e->getMessage()]);
        }
    }

    private function formatOrderData(OrderEntity $order): array
    {
        // Extract primary order details
        $customer = $order->getOrderCustomer();
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getDeliveries()->first()?->getShippingOrderAddress();

        return [
            'order' => [
                'order_id' => $order->getId(),
                'order_date' => $order->getCreatedAt()->format(DateTimeInterface::ATOM),
                'customer' => [
                    'customer_id' => $customer->getCustomerId(),
                    'name' => $customer->getFirstName() . ' ' . $customer->getLastName(),
                    'email' => $customer->getEmail(),
                    'phone' => $customer->getRemoteAddress() // No direct phone field in Shopware OrderCustomer
                ],
                'shipping_address' => $this->formatAddress($shippingAddress),
                'billing_address' => $this->formatAddress($billingAddress),
                'products' => $this->formatProducts($order),
                'total_amount' => $order->getAmountTotal(),
                'currency' => $order->getCurrency()->getIsoCode(),
                'payment' => [
                    'payment_method' => $order->getTransactions()->first()?->getPaymentMethod()->getName(),
                    'payment_provider' => $order->getTransactions()->first()?->getPaymentMethod()->getHandlerIdentifier(),
//                    'payment_state' => $order->getTransactions()->first()?->getStateMachineState()->getTechnicalName()
                ],
                'delivery' => [
                    'delivery_method' => $order->getDeliveries()->first()?->getShippingMethod()->getName(),
                    'tracking_number' => $order->getDeliveries()->first()?->getTrackingCodes()[0] ?? null,
//                    'delivery_status' => $order->getDeliveries()->first()?->getStateMachineState()->getTechnicalName(),
                    'estimated_delivery_date' => $order->getDeliveries()->first()?->getShippingDateEarliest()?->format(\DateTime::ATOM)
                ],
//                'order_status' => $order->getStateMachineState()->getTechnicalName()
            ]
        ];
    }

    private function formatAddress(?OrderAddressEntity $address): array
    {
        return $address ? [
            'name' => $address->getFirstName() . ' ' . $address->getLastName(),
            'street' => $address->getStreet(),
            'city' => $address->getCity(),
            'state' => $address->getCountryState()?->getName(),
            'postal_code' => $address->getZipcode(),
            'country' => $address->getCountry()?->getName()
        ] : [];
    }

    private function formatProducts(OrderEntity $order): array
    {
        $products = [];
        /** @var OrderLineItemEntity $lineItem */
        foreach ($order->getLineItems() as $lineItem) {
            if ($lineItem->getType() === 'product') {
                $products[] = [
                    'product_id' => $lineItem->getProductId(),
                    'name' => $lineItem->getLabel(),
                    'quantity' => $lineItem->getQuantity(),
                    'price' => $lineItem->getUnitPrice(),
                    'currency' => $order->getCurrency()->getIsoCode()
                ];
            }
        }
        return $products;
    }

    public function onOrderDeliveryStateShipped(OrderStateMachineStateChangeEvent $event,): void
    {

        //ToDo Add Webhook call
        $subscriberActive = $this->systemConfigService->getInt('SyncOrderData.config.ddropsSubscriberStatus');
        $webhookUrl = $this->systemConfigService->getString('SyncOrderData.config.ddropsWebHookUrl');

        // Check if the webhook feature is enabled
        if (!$subscriberActive) {
            return;
        }

        $order = $event->getOrder();
        $this->logger->info('Order shipped event triggered', ['orderId' => $order->getId()]);


        $message = new OrderWebhookHandler($order->getId());
//            dd($subscriberActive, $webhookUrl, $order->getId());
        $this->messageBus->dispatch(new DelayedMessage($message, 5));


//        $context = $event->getContext();
//        $eventOrder = $event->getOrder();
//        $order = $this->orderService->getOrderById(
//            $eventOrder->getId(),
//            [
//                'transactions',
//                'transactions.paymentMethod',
//                'transactions.paymentMethod.plugin',
//                'salesChannel',
//                'currency'
//            ],
//            $context
//        );

//        if ($order === null) {
//            $this->logger->debug("Cannot find order entity");
//            return false;
//        }

    }
}
