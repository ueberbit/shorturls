<?php

use UEBERBIT\Shorturls\Hooks\AutoGenerateShortUrlHook;

defined('TYPO3') or die();

call_user_func(function () {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['processDatamapClass']['shorturls'] = AutoGenerateShortUrlHook::class;
});
