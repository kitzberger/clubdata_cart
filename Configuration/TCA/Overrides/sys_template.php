<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die('Access denied.');

ExtensionManagementUtility::addStaticFile(
    'clubdata_cart',
    'Configuration/TypoScript',
    'Clubdata to Cart Connector'
);
