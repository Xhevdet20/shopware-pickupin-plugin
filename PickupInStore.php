<?php

declare(strict_types=1);

namespace PickupInStore;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Plugin;
use Shopware\Core\Framework\Plugin\Context\InstallContext;
use Shopware\Core\Framework\Plugin\Context\UninstallContext;
use Shopware\Core\Framework\Uuid\Uuid;

class PickupInStore extends Plugin
{
    public const SHIPPING_METHOD_TECHNICAL_NAME = 'pickup_in_store';

    public function install(InstallContext $installContext): void
    {
        parent::install($installContext);
        $this->createShippingMethod($installContext->getContext());
    }

    public function uninstall(UninstallContext $uninstallContext): void
    {
        parent::uninstall($uninstallContext);

        if ($uninstallContext->keepUserData()) {
            return;
        }

        $this->deactivateShippingMethod($uninstallContext->getContext());
    }

    private function createShippingMethod(Context $context): void
    {
        /** @var EntityRepository $shippingMethodRepo */
        $shippingMethodRepo = $this->container->get('shipping_method.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', self::SHIPPING_METHOD_TECHNICAL_NAME));
        $existing = $shippingMethodRepo->searchIds($criteria, $context);

        if ($existing->getTotal() > 0) {
            return;
        }

        /** @var EntityRepository $deliveryTimeRepo */
        $deliveryTimeRepo = $this->container->get('delivery_time.repository');
        $deliveryTimes = $deliveryTimeRepo->searchIds(new Criteria(), $context);

        if ($deliveryTimes->getTotal() === 0) {
          
            $deliveryTimeId = Uuid::randomHex();
            $deliveryTimeRepo->create([
                [
                    'id'   => $deliveryTimeId,
                    'name' => '1 - 3 days',
                    'min'  => 1,
                    'max'  => 3,
                    'unit' => 'day',
                ],
            ], $context);
        } else {
            $deliveryTimeId = $deliveryTimes->firstId();
        }

        $shippingMethodRepo->create([
            [
                'id'            => Uuid::randomHex(),
                'name'          => 'Pickup in Store',
                'technicalName' => self::SHIPPING_METHOD_TECHNICAL_NAME,
                'active'        => true,
                'deliveryTimeId' => $deliveryTimeId,
                'description'   => 'Pick up your order directly at our store.',
                'translations'  => [
                    'en-GB' => [
                        'name'        => 'Pickup in Store',
                        'description' => 'Pick up your order directly at our store.',
                    ],
                    'de-DE' => [
                        'name'        => 'Abholung im Geschäft',
                        'description' => 'Holen Sie Ihre Bestellung direkt in unserem Geschäft ab.',
                    ],
                ],
            ],
        ], $context);
    }

    private function deactivateShippingMethod(Context $context): void
    {
        /** @var EntityRepository $shippingMethodRepo */
        $shippingMethodRepo = $this->container->get('shipping_method.repository');

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('technicalName', self::SHIPPING_METHOD_TECHNICAL_NAME));
        $result = $shippingMethodRepo->searchIds($criteria, $context);

        if ($result->getTotal() === 0) {
            return;
        }

        $shippingMethodRepo->update([
            [
                'id'     => $result->firstId(),
                'active' => false,
            ],
        ], $context);
    }
}
