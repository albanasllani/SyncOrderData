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
    private SystemConfigService $configService;
    private HttpClientInterface $httpClient;
    private LoggerInterface $logger;


    public function __construct(
        HttpClientInterface $httpClient,
        LoggerInterface     $logger,
        SystemConfigService $configService
    )
    {
        $this->HttpClientInterface = $httpClient;
        $this->LoggerInterface = $logger;
        $this->SystemConfigService = $configService;

    }

    public function sendWebhook(OrderEntity $order): void
    {
        $webhookUrl = 'https://webhook.site/29032600-2274-4349-ab89-d59f843c2058';

        $payload = $this->formatOrderData($order);


        try {
            $response = $this->httpClient->request('POST', $webhookUrl, [
                'json' => $payload,
                'headers' => [
                    'Content-Type' => 'application/json'
                ]
            ]);

            $this->logger->info('Webhook sent', ['status' => $response->getStatusCode(), 'payload' => $payload]);
        } catch (\Exception $e) {
            $this->logger->error('Webhook failed', ['error' => $e->getMessage()]);
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
                    'payment_state' => $order->getTransactions()->first()?->getStateMachineState()->getTechnicalName()
                ],
                'delivery' => [
                    'delivery_method' => $order->getDeliveries()->first()?->getShippingMethod()->getName(),
                    'tracking_number' => $order->getDeliveries()->first()?->getTrackingCodes()[0] ?? null,
                    'delivery_status' => $order->getDeliveries()->first()?->getStateMachineState()->getTechnicalName(),
                    'estimated_delivery_date' => $order->getDeliveries()->first()?->getShippingDateEarliest()?->format(\DateTime::ATOM)
                ],
                'order_status' => $order->getStateMachineState()->getTechnicalName()
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