<?php

defined('TYPO3') || die('Access denied.');

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    // Backend module icon
    'module-clubdata-cart' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:clubdata_cart/Resources/Public/Icons/module_clubdatacart.svg',
    ],
];
