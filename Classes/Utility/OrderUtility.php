<?php

namespace Medpzl\ClubdataCart\Utility;

use Medpzl\Clubdata\Domain\Repository\ProgramRepository;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder;
use TYPO3\CMS\Extbase\Persistence\Generic\PersistenceManager;

class OrderUtility
{
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

    public function __construct(
        protected ProgramRepository $pogramRepository,
        protected PersistenceManager $persistenceManager,
        protected ProgramRepository $programRepository,
        protected ConfigurationManagerInterface $configurationManager,
        private UriBuilder $uriBuilder
    ) {
        $this->cartConf = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );
        $this->settings = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_SETTINGS,
            'ClubdataCart'
        );
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
            $ticket_number = self::addEanCheck($program_date . $counter);
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

        if ($stock == 0 or $stock < $want) {
            $uri = $this->uriBuilder->reset()
               ->setTargetPageUid($this->cartConf['settings']['cart']['pid'])
                ->setArguments(['tx_cart_cart[quantity_error]' => $want,'tx_cart_cart[action]' => 'updateCart'])
                ->setCreateAbsoluteUri(true)
                ->build();
        }

        return [$params];
    }

    public static function addEanCheck($code)
    {
        $key = 0;
        $mult = [1, 3];

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
