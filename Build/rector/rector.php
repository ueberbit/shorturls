<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\StringableForToStringRector;
use Rector\PostRector\Rector\NameImportingPostRector;
use Rector\Set\ValueObject\LevelSetList;
use Rector\ValueObject\PhpVersion;
use Ssch\TYPO3Rector\CodeQuality\General\ConvertImplicitVariablesToExplicitGlobalsRector;
use Ssch\TYPO3Rector\CodeQuality\General\ExtEmConfRector;
use Ssch\TYPO3Rector\Configuration\Typo3Option;
use Ssch\TYPO3Rector\Set\Typo3LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_83,
        Typo3SetList::CODE_QUALITY,
        Typo3SetList::GENERAL,
        Typo3LevelSetList::UP_TO_TYPO3_14,
    ]);

    $rectorConfig->phpstanConfig(Typo3Option::PHPSTAN_FOR_RECTOR_PATH);

    $rectorConfig->importNames();

    $rectorConfig->disableParallel();

    $rectorConfig->importShortClasses(false);

    $rectorConfig->phpVersion(PhpVersion::PHP_83);

    $rectorConfig->paths([
        __DIR__ . '/../../',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/../../.Build/*',
        __DIR__ . '/../../.github/*',
        __DIR__ . '/../../Build/*',
        __DIR__ . '/../../Tests/*',
        __DIR__ . '/../../var/*',
        // ButtonInterface already extends \Stringable (via ComponentInterface)
        StringableForToStringRector::class => [
            __DIR__ . '/../../Classes/Backend/ShortUrlCopyButton.php',
        ],
        NameImportingPostRector::class => [
            'ext_localconf.php',
            'ext_tables.php',
            'ClassAliasMap.php',
            __DIR__ . '/**/Configuration/*.php',
            __DIR__ . '/**/Configuration/**/*.php',
        ],
    ]);

    $rectorConfig->rule(ConvertImplicitVariablesToExplicitGlobalsRector::class);
    $rectorConfig->ruleWithConfiguration(ExtEmConfRector::class, [
        ExtEmConfRector::ADDITIONAL_VALUES_TO_BE_REMOVED => [],
    ]);
};
