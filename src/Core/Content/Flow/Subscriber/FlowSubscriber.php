<?php declare(strict_types=1);

namespace SyncOrderData\Core\Content\Flow\Subscriber;

use Dotdigital\Flow\Core\Framework\Event\SendOrderStatusUpdateAware;
use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class FlowSubscriber implements EventSubscriberInterface
{
    private BusinessEventCollector $businessEventCollector;

    public function __construct(BusinessEventCollector $businessEventCollector)
    {
        $this->businessEventCollector = $businessEventCollector;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BusinessEventCollectorEvent::NAME => 'onRegisterActions'
        ];
    }

    public function onRegisterActions(BusinessEventCollectorEvent $event): void
    {
        foreach ($event->getCollection()->getElements() as $definition) {
            $definition->addAware(SendOrderStatusUpdateAware::class);
        }
    }



}