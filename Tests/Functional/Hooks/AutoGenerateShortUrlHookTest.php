<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Tests\Functional\Hooks;

use TYPO3\CMS\Core\Site\SiteFinder;
use UEBERBIT\Shorturls\Hooks\AutoGenerateShortUrlHook;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class AutoGenerateShortUrlHookTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/cms-redirects',
        'shorturls',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BeUsers.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/Pages.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysRedirect.csv');
        $this->setUpSite(1, 'https://www.example.org/', ['shorturls_auto_generate' => true]);

        $GLOBALS['BE_USER'] = $this->setUpBackendUser(1);
        $GLOBALS['BE_USER']->user['admin'] = 1;
    }

    public function importCSVDataSet(string $path): void
    {
        $fileName = basename($path, '.csv');
        $table = match ($fileName) {
            'BeUsers' => 'be_users',
            'Pages' => 'pages',
            'SysRedirect' => 'sys_redirect',
            default => strtolower($fileName),
        };
        $csv = array_map(fn($line) => str_getcsv($line, ',', '"', ''), file($path));
        $header = array_shift($csv);
        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable($table);
        foreach ($csv as $row) {
            $values = array_combine($header, $row);
            $queryBuilder->insert($table)->values($values)->executeStatement();
        }
    }

    private function setUpSite(int $pageId, string $base, array $configuration = [], string $identifier = 'example-site'): void
    {
        $siteConfiguration = [
            'base' => $base,
            'languages' => [
                0 => [
                    'title' => 'English',
                    'enabled' => true,
                    'locale' => 'en_US.UTF-8',
                    'hreflang' => 'en-US',
                    'websiteTitle' => '',
                    'navigationTitle' => 'English',
                    'flag' => 'global',
                    'languageId' => 0,
                ],
            ],
            'rootPageId' => $pageId,
            'websiteTitle' => '',
        ];
        $siteConfiguration = array_merge($siteConfiguration, $configuration);

        $folder = $this->instancePath . '/typo3conf/sites/' . $identifier;
        if (!is_dir($folder)) {
            GeneralUtility::mkdir_deep($folder);
        }
        file_put_contents($folder . '/config.yaml', \Symfony\Component\Yaml\Yaml::dump($siteConfiguration));

        // Clear site cache to ensure the new configuration is picked up
        $this->getContainer()->get(\TYPO3\CMS\Core\Cache\CacheManager::class)->getCache('core')->remove('sites-configuration');
    }

    public function testShortUrlIsGeneratedOnPageCreation(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                'NEW1' => [
                    'pid' => 1,
                    'title' => 'New Page',
                ],
            ],
        ];
        $dataHandler->start($data, []);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->process_datamap();

        $newPageId = $dataHandler->substNEWwithIDs['NEW1'] ?? 0;
        self::assertGreaterThan(0, $newPageId, 'Page was not created');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_redirect');
        $redirect = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter('www.example.org')),
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $newPageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertNotEmpty($redirect);
        self::assertEquals('www.example.org', $redirect['source_host']);
        self::assertNotEmpty($redirect['source_path']);
        self::assertEquals('Automatically generated Short URL', $redirect['description']);
    }

    public function testShortUrlIsNotGeneratedForNonDefaultDoktypeByDefault(): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                'NEW1' => [
                    'pid' => 1,
                    'title' => 'New Folder',
                    'doktype' => 254,
                ],
            ],
        ];
        $dataHandler->start($data, []);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->process_datamap();

        $newPageId = $dataHandler->substNEWwithIDs['NEW1'] ?? 0;
        self::assertGreaterThan(0, $newPageId, 'Page was not created');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_redirect');
        $redirect = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $newPageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertFalse($redirect, 'No Short URL should have been generated for a non-default page type');
    }

    public function testShortUrlIsGeneratedForConfiguredDoktype(): void
    {
        $this->setUpSite(1, 'https://www.example.org/', [
            'shorturls_auto_generate' => true,
            // Matches the array shape the backend actually persists for a
            // multi-select site config field (as opposed to a hand-written
            // comma-separated string, which is also supported).
            'shorturls_auto_generate_doktypes' => ['1', '254'],
        ]);

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                'NEW1' => [
                    'pid' => 1,
                    'title' => 'New Folder',
                    'doktype' => 254,
                ],
            ],
        ];
        $dataHandler->start($data, []);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->process_datamap();

        $newPageId = $dataHandler->substNEWwithIDs['NEW1'] ?? 0;
        self::assertGreaterThan(0, $newPageId, 'Page was not created');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_redirect');
        $redirect = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $newPageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertNotEmpty($redirect, 'Short URL should have been generated for an explicitly configured page type');
    }

    public function testShortUrlIsNotGeneratedForSiterootByDefault(): void
    {
        // The new page will get uid 2 in this fresh test database. Since it
        // is flagged as is_siteroot, it needs its own site configuration
        // (e.g. a nested country subsite) - otherwise TYPO3 resolves it to
        // an auto-generated fallback site that has none of our custom
        // flags set, which would make this test pass for the wrong reason.
        $this->setUpSite(2, 'https://www.example.org/de/', [
            'shorturls_auto_generate' => true,
        ], 'subsite');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                'NEW1' => [
                    'pid' => 1,
                    'title' => 'New Site Root',
                    'is_siteroot' => 1,
                ],
            ],
        ];
        $dataHandler->start($data, []);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->process_datamap();

        $newPageId = $dataHandler->substNEWwithIDs['NEW1'] ?? 0;
        self::assertGreaterThan(0, $newPageId, 'Page was not created');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_redirect');
        $redirect = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $newPageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertFalse($redirect, 'No Short URL should have been generated for a site root page by default');
    }

    public function testShortUrlIsGeneratedForSiterootWhenAllowed(): void
    {
        // See testShortUrlIsNotGeneratedForSiterootByDefault() for why the
        // new page (uid 2) needs its own site configuration.
        $this->setUpSite(2, 'https://www.example.org/de/', [
            'shorturls_auto_generate' => true,
            'shorturls_button_on_siteroot' => true,
        ], 'subsite');

        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $data = [
            'pages' => [
                'NEW1' => [
                    'pid' => 1,
                    'title' => 'New Site Root',
                    'is_siteroot' => 1,
                ],
            ],
        ];
        $dataHandler->start($data, []);
        $dataHandler->bypassAccessCheckForRecords = true;
        $dataHandler->process_datamap();

        $newPageId = $dataHandler->substNEWwithIDs['NEW1'] ?? 0;
        self::assertGreaterThan(0, $newPageId, 'Page was not created');

        $queryBuilder = $this->getConnectionPool()->getQueryBuilderForTable('sys_redirect');
        $redirect = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $newPageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAssociative();

        self::assertNotEmpty($redirect, 'Short URL should have been generated for a site root page when explicitly allowed');
    }
}
