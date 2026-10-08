<?php

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use UEBERBIT\Shorturls\Backend\DoktypeItemsProcFunc;

defined('TYPO3') or die();

$GLOBALS['SiteConfiguration']['site']['columns']['shorturls_button_doktypes'] = [
    'label' => 'shorturls.messages:site_config.button_doktypes',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectMultipleSideBySide',
        'items' => [],
        'itemsProcFunc' => DoktypeItemsProcFunc::class . '->populateItems',
        'itemGroups' => $GLOBALS['TCA']['pages']['columns']['doktype']['config']['itemGroups'] ?? [],
        'default' => (string)PageRepository::DOKTYPE_DEFAULT,
        'size' => 5,
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['shorturls_button_on_siteroot'] = [
    'label' => 'shorturls.messages:site_config.button_on_siteroot',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'default' => 0,
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['shorturls_auto_generate'] = [
    'label' => 'shorturls.messages:site_config.auto_generate',
    'config' => [
        'type' => 'check',
        'renderType' => 'checkboxToggle',
        'default' => 0,
    ],
];

$GLOBALS['SiteConfiguration']['site']['columns']['shorturls_auto_generate_doktypes'] = [
    'label' => 'shorturls.messages:site_config.auto_generate_doktypes',
    'displayCond' => 'FIELD:shorturls_auto_generate:REQ:true',
    'config' => [
        'type' => 'select',
        'renderType' => 'selectMultipleSideBySide',
        'items' => [],
        'itemsProcFunc' => DoktypeItemsProcFunc::class . '->populateItems',
        'itemGroups' => $GLOBALS['TCA']['pages']['columns']['doktype']['config']['itemGroups'] ?? [],
        'default' => (string)PageRepository::DOKTYPE_DEFAULT,
        'size' => 5,
    ],
];

$GLOBALS['SiteConfiguration']['site']['types']['0']['showitem'] .= ', --div--;shorturls.messages:site_config.tab, shorturls_button_doktypes, shorturls_button_on_siteroot, shorturls_auto_generate, shorturls_auto_generate_doktypes';
