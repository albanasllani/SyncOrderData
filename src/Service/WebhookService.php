<?php

namespace SyncOrderData\Service;


use DateTimeInterface;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderAddress\OrderAddressEntity;
use Shopware\Core\Checkout\Order\Aggregate\OrderLineItem\OrderLineItemEntity;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Psr\Log\LoggerInterface;
use Shopware\Core\System\SystemConfig\SystemConfigService;


class WebhookService
{

    private LoggerInterface $logger;

    protected SystemConfigService $systemConfigService;

    public function __construct(
        LoggerInterface     $logger,
        SystemConfigService $systemConfigService,
    )
    {
        $this->logger = $logger;
        $this->systemConfigService = $systemConfigService;

    }

    public function sendWebhook(OrderEntity $order): void
    {
        $webhookUrl = $this->systemConfigService->getString('SyncOrderData.config.ddropsWebHookUrl');
//      $webhookUrl = 'https://webhook.site/29032600-2274-4349-ab89-d59f843c2058';
        $payload = $this->formatOrderData($order);

        try {

            $curl = curl_init();

            curl_setopt_array($curl, array(
                //  Live  https://webhook.site/5138e751-daad-4b28-b540-cd87b51131a1
                //  My URL to test: https://webhook.site/29032600-2274-4349-ab89-d59f843c2058
                CURLOPT_URL => $webhookUrl,
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

            $this->logger->info('Webhook sent 199555778 ', ['status' => $response]);
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
        $transaction = $order->getTransactions()->first();
        $delivery = $order->getDeliveries()->first();


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
                    'payment_method' => $transaction?->getPaymentMethod()?->getName(),
                    'payment_provider' => $transaction?->getPaymentMethod()?->getHandlerIdentifier(),
                    'payment_state' => $transaction?->getStateMachineState()?->getTechnicalName(),
                ],
                'delivery' => [
                    'delivery_method' => $delivery?->getShippingMethod()?->getName(),
                    'tracking_number' => $delivery?->getTrackingCodes()[0] ?? null,
                    'delivery_status' => $delivery?->getStateMachineState()?->getTechnicalName(),
                    'estimated_delivery_date' => $delivery?->getShippingDateEarliest()?->format(DateTimeInterface::ATOM),
                ],
                'order_status' => $order->getStateMachineState()?->getTechnicalName(),
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
}