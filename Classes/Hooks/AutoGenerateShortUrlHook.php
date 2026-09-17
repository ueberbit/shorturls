<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Hooks;

use UEBERBIT\Shorturls\Configuration\DoktypeConfiguration;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\ShortUrlService;

#[Autoconfigure(public: true)]
final readonly class AutoGenerateShortUrlHook
{
    public function __construct(
        private ConnectionPool         $connectionPool,
        private ShortUrlService        $shortUrlService,
        private SiteFinder             $siteFinder,
        private CacheManager           $cacheManager,
        private LanguageServiceFactory $languageServiceFactory,
        private RedirectCacheService   $redirectCacheService
    ) {}

    /**
     * @param string $status
     * @param string $table
     * @param string|int $id
     * @param array $fieldArray
     * @param DataHandler $dataHandler
     */
    public function processDatamap_afterDatabaseOperations(string $status, string $table, $id, array $fieldArray, DataHandler $dataHandler): void
    {
        if ($status !== 'new' || $table !== 'pages') {
            return;
        }

        if (array_key_exists($id, $dataHandler->substNEWwithIDs)) {
            $pageId = (int)$dataHandler->substNEWwithIDs[$id];
        } else {
            return;
        }

        try {
            $site = $this->siteFinder->getSiteByPageId($pageId);
        } catch (\Exception) {
            return;
        }

        if (!($site->getConfiguration()['shorturls_auto_generate'] ?? false)) {
            return;
        }

        $allowedDoktypes = DoktypeConfiguration::getAllowedDoktypes($site->getConfiguration(), 'shorturls_auto_generate_doktypes');
        $doktype = (int)($fieldArray['doktype'] ?? PageRepository::DOKTYPE_DEFAULT);

        if (!in_array($doktype, $allowedDoktypes, true)) {
            return;
        }

        $isSiteRoot = (bool)($fieldArray['is_siteroot'] ?? false);
        if ($isSiteRoot && !($site->getConfiguration()['shorturls_button_on_siteroot'] ?? false)) {
            return;
        }

        $sourceHost = $site->getBase()->getHost() ?: '*';

        // Prüfen ob bereits eine ShortURL existiert
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $count = $queryBuilder
            ->count('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter($sourceHost)),
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $pageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, \TYPO3\CMS\Core\Database\Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        if ((int)$count === 0) {
            $sourcePath = $this->shortUrlService->generateUniqueShortUrlPath($sourceHost);

            if ($sourcePath !== null) {
                $languageService = $this->languageServiceFactory->createFromUserPreferences($GLOBALS['BE_USER']);
                $description = $languageService->sL('LLL:EXT:shorturls/Resources/Private/Language/locallang.xlf:redirect_description');

                $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
                $queryBuilder
                    ->insert('sys_redirect')
                    ->values([
                        'pid' => 0,
                        'redirect_type' => 'short_url',
                        'source_host' => $sourceHost,
                        'source_path' => $sourcePath,
                        'target' => 't3://page?uid=' . $pageId,
                        'target_statuscode' => 307,
                        'description' => $description,
                        'createdon' => time(),
                        'updatedon' => time(),
                        'creation_type' => 1,
                    ])
                    ->executeStatement();

                $this->cacheManager->flushCachesByTag('pageId_' . $pageId);
                $this->redirectCacheService->rebuildForHost($sourceHost);
            }
        }
    }
}
