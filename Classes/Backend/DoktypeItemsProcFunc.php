<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Backend;

final class DoktypeItemsProcFunc
{
    public function populateItems(array &$config): void
    {
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] ?? [] as $item) {
            if (($item['value'] ?? null) === '--div--') {
                continue;
            }
            $config['items'][] = $item;
        }
    }
}
