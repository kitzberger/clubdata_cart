<?php

namespace Medpzl\ClubdataCart\ViewHelpers;

use Extcode\Cart\Domain\Model\Cart\Cart;
use Medpzl\ClubdataCart\Domain\Repository\PauseRepository;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

class CheckShippingViewHelper extends AbstractViewHelper
{
    public function __construct(
        PauseRepository $pauseRepository,
        ConfigurationManagerInterface $configurationManager
    ) {
    }

    public function initializeArguments()
    {
        $this->registerArgument('cart', Cart::class, 'Cart object', true);
    }

    public function render()
    {
        $cart = $this->arguments['cart'];
        
        $cartConf = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );

        $deadline = $cartConf['settings']['shipDeadline'];
        $products = $cart->getProducts();

        foreach ($products as $product) {
            $date = substr($product->getTitle(), -14);
            $date = substr($date, 0, 6) . '20' . substr($date, 6, 2);

            $result = $this->pauseRepository->findPause(date('U'));

            if (count($result)) {
                return $result[0]->getTitle();
            }

            if (date('U') > strtotime($date . ' ' . $deadline)) {
                return 'error';
            }
        }

        return '';
    }
}
