<?php
defined('TYPO3_MODE') or die ('Access denied.');

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'Libeo.lbo_health',
    'Health',
    [
        'Health' => 'status'
    ],
    []
);
