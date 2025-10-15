<?php

declare(strict_types=1);

use Medpzl\ClubdataCart\Controller\BackendController;

/**
 * Backend module configuration for TYPO3 13
 */
return [
    'cart_clubdatacart' => [
        'parent' => 'cart_cart_main',
        'position' => ['bottom'],
        'access' => 'user, group',
        'workspaces' => 'live',
        'iconIdentifier' => 'module-clubdata-cart',
        'path' => '/module/cartcart/clubdatacart',
        'labels' => 'LLL:EXT:clubdata_cart/Resources/Private/Language/locallang_db.xlf:tx_clubdatacart.module.clubdata',
        'extensionName' => 'ClubdataCart',
        'controllerActions' => [
            BackendController::class => [
                'interface',
                'ticketExport',
                'ticketCheck',
                'ticketCheckDetail',
                'ticketCheckWrite',
                'refundCheckOrders',
                'refundOrders',
                'refundOrder',
            ],
        ],
        'navigationComponentId' => 'typo3-pagetree',
    ],
];
