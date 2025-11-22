<?php

declare(strict_types=1);

namespace Medpzl\ClubdataCart\EventListener;

use Extcode\Cart\Domain\Model\Cart\Product;
use Extcode\Cart\Domain\Model\Cart\TaxClass;
use Extcode\Cart\Event\RetrieveProductsFromRequestEvent;
use Medpzl\Clubdata\Domain\Repository\ProgramRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

final class RetrieveProductsFromRequest
{
    public function __construct(
        private ProgramRepository $programRepository,
        private ConfigurationManagerInterface $configurationManager
    ) {}

    public function __invoke(RetrieveProductsFromRequestEvent $event): void
    {
        $request = $event->getRequest();
        $productType = $request->getArgument('productType');
        $productUid = $request->getArgument('productId');
        $quantity = (int)($request->getArgument('quantity') ?? 1);

        if ($productType === 'program') {
            $this->cartConf = $this->configurationManager->getConfiguration(
                ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
                'Cart'
            );
            $taxClassKey = 2;
            $taxClasses = $this->cartConf['taxClasses'];

            $program = $this->programRepository->findByUid($productUid);

            $stock = 0;
            $handleStock = false;

            if ($program->getMaxTickets() > 0) {
                $handleStock = true;
                $stock = $program->getMaxTickets() - $program->getSoldTickets();
            }

            $taxClass = GeneralUtility::makeInstance(
                TaxClass::class,
                id: (int)$taxClassKey,
                value: (string)$taxClasses[$taxClassKey]['value'],
                calc: (float)$taxClasses[$taxClassKey]['calc'],
                title: (string)$taxClasses[$taxClassKey]['name']
            );

            $cartProduct = new Product(
                productType: 'vitual', // TODO: what is vitual?!
                productId: $program->getUid(),
                sku: (string)$program->getUid(),
                title: $program->getTitle() . ' ' . $program->getDatetime()->format('d.m.y H:i'),
                price: (float)$program->getPriceB(),
                taxClass: $taxClass,
                quantity: $quantity,
                isNetPrice: false,
                feVariant: null
            );
            $cartProduct->setStock($stock);
            $cartProduct->setHandleStock($handleStock);

            $event->addProduct($cartProduct);
        }
    }
}
