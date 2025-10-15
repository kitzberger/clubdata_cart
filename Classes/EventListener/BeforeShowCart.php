<?php

declare(strict_types=1);

namespace Medpzl\ClubdataCart\EventListener;

use Extcode\Cart\Domain\Model\Order\BillingAddress;
use Extcode\Cart\Event\Cart\BeforeShowCartEvent;
use TYPO3\CkClubdata\Domain\Model\FrontendUser;
use TYPO3\CkClubdata\Domain\Repository\FrontendUserRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class BeforeShowCart
{
    public function __construct(
        private readonly Context $context,
        private readonly FrontendUserRepository $frontendUserRepository
    ) {
    }

    /**
     * Enrich the cart variables by the frontend users data as billing address.
     *
     * Called by EXT:cart/Controller/Cart/CartController->showAction()
     */
    public function __invoke(BeforeShowCartEvent $event): void
    {
        $billingAddress = $event->getBillingAddress();

        if (is_null($billingAddress)) {
            $billingAddress = GeneralUtility::makeInstance(BillingAddress::class);
        }

        if ($billingAddress->_isNew() === false) {
            return;
        }

        $feUserUid = $this->context->getPropertyFromAspect(
            'frontend.user',
            'id',
            null,
        );

        $frontendUser = $this->frontendUserRepository->findByUid($feUserUid);

        if ($frontendUser instanceof FrontendUser) {
            $billingAddress->setEmail($frontendUser->getEmail());
            $billingAddress->setTitle($frontendUser->getTitle());
            $billingAddress->setSalutation($frontendUser->getSalutation());
            $billingAddress->setFirstName($frontendUser->getFirstName());
            $billingAddress->setLastName($frontendUser->getLastName());
            $billingAddress->setCompany($frontendUser->getCompany());
            $billingAddress->setStreet($frontendUser->getAddress());
            $billingAddress->setZip($frontendUser->getZip());
            $billingAddress->setCity($frontendUser->getCity());
            $billingAddress->setPhone($frontendUser->getTelephone());

            $event->setBillingAddress($billingAddress);
            $event->setPropagationStopped(true);
        }
    }
}
