<?php

namespace Medpzl\ClubdataCart\Utility;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
/**
 * Address Service
 *
 * @author CK
 */
class OrderUtility
{
    /**
     * Persistence Manager
     *
     * @var \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager
     */
    protected $persistenceManager;
    /**
     * Configuration Manager
     *
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;
    /**
     * Cart Repository
     *
     * @var \Extcode\Cart\Domain\Repository\CartRepository
     */
    protected $cartRepository;
    /**
     * Cart Settings
     *
     * @var array
     */
    protected $cartConf = [];

    /**
     * OrderUtility Item
     *
     * @var \Extcode\Cart\Domain\Model\Order\Item
     */
    protected $orderItem = null;
    /**
     * Cart
     *
     * @var \Extcode\Cart\Domain\Model\Cart\Cart
     */
    protected $cart = null;
    /**
     * CartFHash
     *
     * @var string
     */
    protected $cartFHash = '';

    /**
     * ProgramRepository
     *
     * @var \TYPO3\CkClubdata\Domain\Repository\ProgramRepository
     */
    protected $programRepository = null;
    /**
     * Intitialize
     */
    public function __construct(
        private \TYPO3\CkClubdata\Domain\Repository\ProgramRepository $pogramRepository,
        \TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager $persistenceManager,
        \TYPO3\CkClubdata\Domain\Repository\ProgramRepository $programRepository,
        \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager,
        private \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder $uriBuilder
    ) {
        $this->configurationManager = $configurationManager;
        $this->cartConf = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );
        $this->settings = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'ClubdataCart'
        );
        $this->persistenceManager = $persistenceManager;
        $this->programRepository = $programRepository;
    }

    /**
     * Handle Payment - Signal Slot Function
     *
     * @param array $params
     *
     * @return array
     */
    public function changeOrderItemBeforeSaving($params): void
    {
        foreach ($params['orderItem']->getProducts() as $product) {
            $program = $this->programRepository->findByUid($product->getSku());
            $sold = $program->getsoldTickets();
            $want = $product->getCount();
            $new = $sold;
            $init = $new;
            $new += $want;
            $init++; //startnummer Neues Ticket
            $counter = $this->settings['ticket']['numberPrefix'] . sprintf($this->settings['ticket']['numberFormat'], $init); // Kartenzähler
            $program_date = $program->getDateTime()->format('ymdH');
            $ticket_number = $this->addEanCheck($program_date . $counter);
            $program->setsoldTickets($new);
            $product->setproductType($ticket_number);
            $this->programRepository->update($program);
        }
    }


    /**
     * CheckStock - Signal Slot Function
     *
     * @param array $params
     *
     * @return array
     */
    public function checkStock($params)
    {
        $product = $params['cartProduct'];
        $product->setHandleStock(true);
        $program = $this->programRepository->findByUid($product->getSku());

        $stock = 0;
        if ($program->getmaxTickets() > 0) {
            $stock = $program->getmaxTickets() - $program->getsoldTickets();
        }
        $want = $product->getQuantity();

        if ($stock==0 or $stock < $want) {
            $uri = $this->uriBuilder->reset()
               ->setTargetPageUid($this->cartConf['settings']['cart']['pid'])
                ->setArguments(['tx_cart_cart[quantity_error]'=>$want,'tx_cart_cart[action]'=>'updateCart'])
                ->setCreateAbsoluteUri(true)
                ->build();
        }

        return [$params];
    }

    private function addEanCheck($code)
    {
        $key = 0;
        $mult = [ 1, 3 ];

        for ($i = 0; $i < strlen($code); $i++) {
            $key += substr($code, $i, 1) * $mult[$i % 2];
        }

        $key = 10 - ($key % 10);

        if ($key == 10) {
            $key = 0;
        }

        // in key steht die prüfziffer - an den code anhängen
        $code .= $key;
        return $code;
    }
}
