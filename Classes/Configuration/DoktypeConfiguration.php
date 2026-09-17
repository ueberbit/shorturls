<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Configuration;

use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DoktypeConfiguration
{
    /**
     * Reads a doktype-multiselect site configuration value. Backend-saved
     * values are arrays of value strings (e.g. ['1', '254']), while
     * hand-written config.yaml may use a comma-separated string instead.
     *
     * @return int[]
     */
    public static function getAllowedDoktypes(array $siteConfiguration, string $key): array
    {
        $value = $siteConfiguration[$key] ?? (string)PageRepository::DOKTYPE_DEFAULT;

        if (is_array($value)) {
            return array_map('intval', $value);
        }

        return GeneralUtility::intExplode(',', (string)$value, true);
    }
}
