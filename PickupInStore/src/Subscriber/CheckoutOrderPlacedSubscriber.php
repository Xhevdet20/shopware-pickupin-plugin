<?php declare(strict_types=1);

namespace PickupInStore\Subscriber;

use Shopware\Core\Checkout\Cart\Event\CheckoutOrderPlacedEvent;
use Shopware\Core\Checkout\Order\OrderEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CheckoutOrderPlacedSubscriber implements EventSubscriberInterface
{
    private const CONFIG_PREFIX = 'PickupInStore.config.';

    public function __construct(
        private readonly SystemConfigService $systemConfigService,
        private readonly EntityRepository $orderRepository,
        private readonly EntityRepository $orderAddressRepository
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CheckoutOrderPlacedEvent::class => 'onCheckoutOrderPlaced',
        ];
    }

    public function onCheckoutOrderPlaced(CheckoutOrderPlacedEvent $event): void
    {
        $pickupShippingMethodId = $this->readRequiredConfigValue('pickupShippingMethodId');
        if ($pickupShippingMethodId === null) {
            return;
        }

        $storeAddressPayload = $this->buildStoreAddressPayload();
        if ($storeAddressPayload === null) {
            return;
        }

        $criteria = (new Criteria([$event->getOrderId()]))
            ->addAssociation('deliveries');

        $order = $this->orderRepository->search($criteria, $event->getContext())->first();
        if (!$order instanceof OrderEntity || $order->getDeliveries() === null) {
            return;
        }

        $addressUpdates = [];

        foreach ($order->getDeliveries() as $delivery) {
            if ($delivery->getShippingMethodId() !== $pickupShippingMethodId) {
                continue;
            }

            $shippingOrderAddressId = $delivery->getShippingOrderAddressId();
            if ($shippingOrderAddressId === null || $shippingOrderAddressId === '') {
                continue;
            }

            $addressUpdates[] = ['id' => $shippingOrderAddressId, ...$storeAddressPayload];
        }

        if ($addressUpdates === []) {
            return;
        }

        $this->orderAddressRepository->update($addressUpdates, $event->getContext());
    }

    private function buildStoreAddressPayload(): ?array
    {
        $requiredFieldMap = [
            'salutationId' => 'storeSalutationId',
            'firstName' => 'storeFirstName',
            'lastName' => 'storeLastName',
            'street' => 'storeStreet',
            'zipcode' => 'storeZipcode',
            'city' => 'storeCity',
            'countryId' => 'storeCountryId',
        ];

        $payload = [];

        foreach ($requiredFieldMap as $addressField => $configField) {
            $value = $this->readRequiredConfigValue($configField);
            if ($value === null) {
                return null;
            }

            $payload[$addressField] = $value;
        }

        $optionalFieldMap = [
            'company' => 'storeCompany',
            'phoneNumber' => 'storePhoneNumber',
            'additionalAddressLine1' => 'storeAdditionalAddressLine1',
            'additionalAddressLine2' => 'storeAdditionalAddressLine2',
        ];

        foreach ($optionalFieldMap as $addressField => $configField) {
            $value = $this->readOptionalConfigValue($configField);
            if ($value !== null) {
                $payload[$addressField] = $value;
            }
        }

        return $payload;
    }

    private function readRequiredConfigValue(string $key): ?string
    {
        $value = $this->readOptionalConfigValue($key);

        if ($value === null || $value === '') {
            return null;
        }

        return $value;
    }

    private function readOptionalConfigValue(string $key): ?string
    {
        $value = $this->systemConfigService->get(self::CONFIG_PREFIX . $key);

        if (!\is_scalar($value)) {
            return null;
        }

        $trimmedValue = trim((string) $value);

        return $trimmedValue === '' ? null : $trimmedValue;
    }
}
