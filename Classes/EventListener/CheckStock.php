<?php

declare(strict_types=1);

namespace Medpzl\ClubdataCart\EventListener;

use Extcode\Cart\Event\CheckProductAvailabilityEvent;
use Medpzl\Clubdata\Domain\Repository\ProgramRepository;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class CheckStock
{
    public function __construct(
        protected ProgramRepository $programRepository,
    ) {}

    /**
     * Decides where or not a product is still available
     *
     * Called by
     * - EXT:cart/Classes/Controller/Cart/CartController.php
     * - EXT:cart/Classes/Controller/Cart/ProductController.php
     */
    public function __invoke(CheckProductAvailabilityEvent $event): void
    {
        $product = $event->getProduct();
        $product->setHandleStock(true);

        $program = $this->programRepository->findByUid($product->getSku());

        $stock = 0;
        if ($program->getMaxTickets() > 0) {
            $stock = $program->getMaxTickets() - $program->getSoldTickets();
        }
        $demand = $product->getQuantity();

        if ($stock == 0 || $stock < $demand) {
            $message = GeneralUtility::makeInstance(
                FlashMessage::class,
                message: 'Leider stehen für diese Veranstaltung keine Tickets mehr zur Verfügung.',
                title: 'Keine Tickets mehr verfübar!',
                severity: ContextualFeedbackSeverity::ERROR,
                storeInSession: true
            );
            $event->addMessage($message);
            $event->setAvailable(false);
        }
    }
}
