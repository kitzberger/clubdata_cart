<?php

namespace Medpzl\ClubdataCart\ViewHelpers;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

class CheckShippingViewHelper extends AbstractViewHelper
{
    /**
     * PauseRepository
     *
     * @var \Medpzl\ClubdataCart\Domain\Repository\PauseRepository
     */
    protected $pauseRepository = null;
    
    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     */
    protected $configurationManager;

    public function __construct(
        \Medpzl\ClubdataCart\Domain\Repository\PauseRepository $pauseRepository,
        \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface $configurationManager
    ) {
        $this->pauseRepository = $pauseRepository;
        $this->configurationManager = $configurationManager;
    }

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument('cart', '\Extcode\Cart\Domain\Model\Cart\Cart', 'Cart object', true);
    }

    /**
     * @return string
     * @api
     */
    public function render()
    {
        $cart = $this->arguments['cart'];
        
        $cartConf = $this->configurationManager->getConfiguration(
            \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Cart'
        );
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($cart);
        //exit;
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($cartConf);
        //if ($cart->getShipping()->getId()==2) {
        $deadline = $cartConf['settings']['shipDeadline'];
        $products = $cart->getProducts();
        //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($products);
        foreach ($products as $product) {
            $date = substr($product->getTitle(), -14);
            $date = substr($date, 0, 6) . '20' . substr($date, 6, 2);
            //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($date);
            //$result = $this->pauseRepository->findPause(strtotime($date.' '.$deadline));
            $result = $this->pauseRepository->findPause(date('U'));
            //$result = $this->pauseRepository->findAll();
            if (count($result)) {
                return $result[0]->getTitle();
            }
            //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($date.' '.$deadline);
            //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump(strtotime($date.' '.$deadline));
            //\TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($result);
            //exit;
            //if ($this->pauseRepository->findPause(strtotime($date.' '.$deadline))) return false;
            if (date('U') > strtotime($date . ' ' . $deadline)) {
                return 'error';
            }
        }
        //}
        return '';
    }

}
