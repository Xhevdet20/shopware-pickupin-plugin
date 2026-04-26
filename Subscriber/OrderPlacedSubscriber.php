<?php

declare(strict_types=1);

namespace PickupInStore\Subscriber;

use PickupInStore\PickupInStore;
use PickupInStore\Service\StoreAddressService;
use Psr\Log\LoggerInterface;
use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryCollection;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class OrderPlacedSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly StoreAddressService $storeAddressService,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onOrderPlaced',
        ];
    }

   
    public function onOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $order = $event->getOrder();

        /** @var OrderDeliveryCollection|null $deliveries */
        $deliveries = $order->getDeliveries();
        if ($deliveries === null || $deliveries->count() === 0) {
            return;
        }

        if (!$this->storeAddressService->isConfigured()) {
            return;
        }

        foreach ($deliveries as $delivery) {
            $shippingMethod = $delivery->getShippingMethod();
            if ($shippingMethod === null) {
                continue;
            }

            if ($shippingMethod->getTechnicalName() !== PickupInStore::SHIPPING_METHOD_TECHNICAL_NAME) {
                continue;
            }

            $this->storeAddressService->overwriteDeliveryAddress($delivery, $event->getContext());
        }
    }
}
