<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\ShortUrlService;

#[Autoconfigure(public: true)]
final readonly class ShortUrlController
{
    public function __construct(
        private ConnectionPool $connectionPool,
        private UriBuilder $uriBuilder,
        private ShortUrlService $shortUrlService,
        private CacheManager $cacheManager,
        private SiteFinder $siteFinder,
        private RedirectCacheService $redirectCacheService,
        private FlashMessageService $flashMessageService,
    ) {}

    public function createAction(ServerRequestInterface $request): ResponseInterface
    {
        $pageId = (int)($request->getQueryParams()['pageId'] ?? 0);

        if ($pageId > 0) {

            try {
                $sourceHost = $this->siteFinder->getSiteByPageId($pageId)->getBase()->getHost() ?: '*';
            } catch (SiteNotFoundException) {
                $sourceHost = '*';
            }

            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');

            // Sicherheitshalber nochmal prüfen, ob bereits einer existiert
            $count = $queryBuilder
                ->count('*')
                ->from('sys_redirect')
                ->where(
                    $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter($sourceHost)),
                    $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $pageId)),
                    $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();

            if ((int)$count === 0) {
                $sourcePath = $this->shortUrlService->generateUniqueShortUrlPath($sourceHost);

                if ($sourcePath !== null) {
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
                            'createdon' => time(),
                            'updatedon' => time(),
                            'creation_type' => 1,
                        ])
                        ->executeStatement();

                    $this->addFlashMessage(sprintf($this->getLanguageService()->sL('shorturls.messages:controller.created'), $sourcePath));
                    $this->cacheManager->flushCachesByTag('pageId_' . $pageId);
                    $this->redirectCacheService->rebuildForHost($sourceHost);
                } else {
                    $this->addFlashMessage($this->getLanguageService()->sL('shorturls.messages:controller.error.generate'), ContextualFeedbackSeverity::ERROR);
                }
            } else {
                $this->addFlashMessage($this->getLanguageService()->sL('shorturls.messages:controller.error.exists'), ContextualFeedbackSeverity::WARNING);
            }
        }

        $returnUrl = $request->getQueryParams()['returnUrl'] ?? (string)$this->uriBuilder->buildUriFromRoute('web_layout', ['id' => $pageId]);
        return new RedirectResponse($returnUrl);
    }

    private function addFlashMessage(string $message, ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::OK): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, '', $severity, true);
        $this->flashMessageService->getMessageQueueByIdentifier()->addMessage($flashMessage);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
