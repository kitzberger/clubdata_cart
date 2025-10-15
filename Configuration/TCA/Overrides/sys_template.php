<?php

defined('TYPO3') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'clubdata_cart',
    'Configuration/TypoScript',
    'Clubdata to Cart Connector'
);
