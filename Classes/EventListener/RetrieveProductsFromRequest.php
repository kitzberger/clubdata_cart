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
    ) {
    }

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

            if ($program->getmaxTickets() > 0) {
                $handleStock = true;
                $stock = $program->getmaxTickets() - $program->getsoldTickets();
            }

            $taxClass = GeneralUtility::makeInstance(
                TaxClass::class,
                (int)$taxClassKey,
                (string)$taxClasses[$taxClassKey]['value'],
                (float)$taxClasses[$taxClassKey]['calc'],
                (string)$taxClasses[$taxClassKey]['name']
            );

            $cartProduct = new Product(
                'vitual', // productType
                $program->getUid(), //productId
                (string)$program->getUid(), //sku
                $program->getTitle() . ' ' . $program->getDatetime()->format('d.m.y H:i'),
                (float)$program->getPriceB(),
                $taxClass,
                $quantity,
                false, // isNetPrice
                null // $feVariant
            );
            $cartProduct->setStock($stock);
            $cartProduct->setHandleStock($handleStock);

            $event->addProduct($cartProduct);
        }
    }
}
