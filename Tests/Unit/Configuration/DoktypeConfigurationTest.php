<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use UEBERBIT\Shorturls\Configuration\DoktypeConfiguration;

final class DoktypeConfigurationTest extends TestCase
{
    public static function allowedDoktypesDataProvider(): array
    {
        return [
            'backend-saved array of value strings' => [
                ['1', '254'],
                [1, 254],
            ],
            'hand-written comma-separated string' => [
                '1,254',
                [1, 254],
            ],
            'single value as string' => [
                '4',
                [4],
            ],
            'empty string falls back to default' => [
                '',
                [],
            ],
        ];
    }

    #[DataProvider('allowedDoktypesDataProvider')]
    public function testGetAllowedDoktypesResolvesConfiguredValue(mixed $configuredValue, array $expected): void
    {
        self::assertSame(
            $expected,
            DoktypeConfiguration::getAllowedDoktypes(['shorturls_test' => $configuredValue], 'shorturls_test')
        );
    }

    public function testGetAllowedDoktypesDefaultsToStandardPageWhenKeyIsMissing(): void
    {
        self::assertSame(
            [PageRepository::DOKTYPE_DEFAULT],
            DoktypeConfiguration::getAllowedDoktypes([], 'shorturls_test')
        );
    }
}
