<?php

declare(strict_types=1);

namespace PickupInStore\Service;

use PickupInStore\PickupInStore;
use Shopware\Core\Checkout\Order\Aggregate\OrderDelivery\OrderDeliveryEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class StoreAddressService
{
    public function __construct(
        private readonly EntityRepository $orderAddressRepository,
        private readonly EntityRepository $countryRepository,
        private readonly SystemConfigService $systemConfigService,
    ) {
    }


    public function overwriteDeliveryAddress(OrderDeliveryEntity $delivery, Context $context): void
    {
        $addressId = $delivery->getShippingOrderAddressId();
        if ($addressId === null) {
            return;
        }

        $countryId = $this->resolveCountryId($context);
        if ($countryId === null) {
            return;
        }

        $updateData = [
            'id'        => $addressId,
            'countryId' => $countryId,
            'firstName' => $this->getConfig('storeFirstName') ?? 'Store',
            'lastName'  => $this->getConfig('storeLastName') ?? 'Pickup',
            'street'    => $this->getConfig('storeStreet') ?? '',
            'zipcode'   => $this->getConfig('storeZipCode') ?? '',
            'city'      => $this->getConfig('storeCity') ?? '',
        ];

        $company = $this->getConfig('storeCompany');
        if ($company !== null && $company !== '') {
            $updateData['company'] = $company;
        }

        $additionalLine = $this->getConfig('storeAdditionalAddressLine1');
        if ($additionalLine !== null && $additionalLine !== '') {
            $updateData['additionalAddressLine1'] = $additionalLine;
        }

        $phone = $this->getConfig('storePhone');
        if ($phone !== null && $phone !== '') {
            $updateData['phoneNumber'] = $phone;
        }

        $this->orderAddressRepository->update([$updateData], $context);
    }

  
    public function isConfigured(): bool
    {
        $required = ['storeFirstName', 'storeLastName', 'storeStreet', 'storeZipCode', 'storeCity', 'storeCountryIso'];

        foreach ($required as $field) {
            $value = $this->getConfig($field);
            if ($value === null || trim((string) $value) === '') {
                return false;
            }
        }

        return true;
    }

    private function resolveCountryId(Context $context): ?string
    {
        $iso = $this->getConfig('storeCountryIso');
        if ($iso === null || trim((string) $iso) === '') {
            return null;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('iso', strtoupper(trim((string) $iso))));
        $criteria->setLimit(1);

        $result = $this->countryRepository->searchIds($criteria, $context);

        return $result->firstId();
    }

    private function getConfig(string $key): mixed
    {
        return $this->systemConfigService->get('PickupInStore.config.' . $key);
    }
}
