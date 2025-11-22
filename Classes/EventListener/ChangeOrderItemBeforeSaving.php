<?php

declare(strict_types=1);

namespace Medpzl\ClubdataCart\EventListener;

use Extcode\Cart\Domain\Model\Order\BillingAddress;
use Extcode\Cart\Event\Order\PersistOrderEvent;
use Medpzl\ClubdataCart\Utility\OrderUtility;
use Medpzl\Clubdata\Domain\Model\FrontendUser;
use Medpzl\Clubdata\Domain\Repository\FrontendUserRepository;
use Medpzl\Clubdata\Domain\Repository\ProgramRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

final class ChangeOrderItemBeforeSaving
{
    private string $numberPrefix;
    private string $numberFormat;

    public function __construct(
        protected ProgramRepository $programRepository,
        protected ConfigurationManagerInterface $configurationManager,
    ) {
        $this->settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'ClubdataCart'
        );

        $this->numberPrefix = $this->settings['ticket']['numberPrefix'];
        $this->numberFormat = $this->settings['ticket']['numberFormat'];
    }

    /**
     * Increases the tx_clubdata_domain_model_program.sold_tickets
     * and sets the tx_cart_domain_model_order_product.product_type
     *
     * Called by EXT:cart/EventListener/Order/Create/Order
     */
    public function __invoke(PersistOrderEvent $event): void
    {
        $orderItem = $event->getOrderItem();

        foreach ($orderItem->getProducts() as $product) {
            $program = $this->programRepository->findByUid($product->getSku());

            $sold = $program->getSoldTickets();
            $want = $product->getCount();
            $new = $sold;
            $init = $new;
            $new += $want;
            $init++; //startnummer Neues Ticket
            $counter = $this->numberPrefix . sprintf($this->numberFormat, $init); // Kartenzähler
            $program_date = $program->getDateTime()->format('ymdH');
            $ticket_number = OrderUtility::addEanCheck($program_date . $counter);
            $program->setSoldTickets($new);
            $product->setProductType($ticket_number);

            $this->programRepository->update($program);
        }
    }
}
