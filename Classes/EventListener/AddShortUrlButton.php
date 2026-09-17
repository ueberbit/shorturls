<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\EventListener;

use UEBERBIT\Shorturls\Configuration\DoktypeConfiguration;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\Buttons\LinkButton;
use TYPO3\CMS\Backend\Template\Components\ModifyButtonBarEvent;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconSize;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;

#[AsEventListener(
    identifier: 'shorturls/add-button'
)]
final readonly class AddShortUrlButton
{
    public function __construct(
        private IconFactory    $iconFactory,
        private ConnectionPool $connectionPool,
        private UriBuilder     $uriBuilder
    ) {}

    public function __invoke(ModifyButtonBarEvent $event): void
    {
        $request = $GLOBALS['TYPO3_REQUEST'] ?? null;
        if ($request === null) {
            return;
        }

        $pageRenderer = $request->getAttribute('normalizedParams') ? GeneralUtility::makeInstance(PageRenderer::class) : null;
        if ($pageRenderer instanceof PageRenderer) {
            $pageRenderer->getJavaScriptRenderer()->addJavaScriptModuleInstruction(
                JavaScriptModuleInstruction::create('@ueberbit/shorturls/short-url-actions')
            );
        }

        $pageId = (int)($request->getQueryParams()['id'] ?? 0);
        if ($pageId === 0) {
            return;
        }

        $site = $request->getAttribute('site');
        if (!$site instanceof Site) {
            return;
        }

        $pagesQueryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $page = $pagesQueryBuilder
            ->select('doktype', 'is_siteroot')
            ->from('pages')
            ->where($pagesQueryBuilder->expr()->eq('uid', $pagesQueryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative();

        $allowedDoktypes = DoktypeConfiguration::getAllowedDoktypes($site->getConfiguration(), 'shorturls_button_doktypes');
        if (!in_array((int)($page['doktype'] ?? 0), $allowedDoktypes, true)) {
            return;
        }

        $isSiteRoot = (bool)($page['is_siteroot'] ?? false);
        if ($isSiteRoot && !($site->getConfiguration()['shorturls_button_on_siteroot'] ?? false)) {
            return;
        }

        $baseUrl = $site->getBase()->__toString();
        $sourceHost = $site->getBase()->getHost() ?: '*';

        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_redirect');
        $redirect = $queryBuilder
            ->select('*')
            ->from('sys_redirect')
            ->where(
                $queryBuilder->expr()->eq('source_host', $queryBuilder->createNamedParameter($sourceHost)),
                $queryBuilder->expr()->eq('target', $queryBuilder->createNamedParameter('t3://page?uid=' . $pageId)),
                $queryBuilder->expr()->eq('creation_type', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->setMaxResults(1)
            ->orderBy('updatedon', 'DESC')
            ->executeQuery()
            ->fetchAssociative();

        $languageService = $this->getLanguageService();

        if (!empty($redirect)) {
            $shortUrl = $redirect['source_path'] ?? '';
            $fullUrl = rtrim($baseUrl, '/') . '/' . ltrim($shortUrl, '/');
            $title = sprintf($languageService->sL('LLL:EXT:shorturls/Resources/Private/Language/locallang.xlf:button.title'), $shortUrl);

            $href = '#';
            $attributes = [
                'data-shorturl-copy' => $fullUrl,
                'data-shorturl-display' => $shortUrl,
                'data-shorturl-notification-title' => $languageService->sL('LLL:EXT:shorturls/Resources/Private/Language/locallang.xlf:notification.copied.title'),
                'data-shorturl-notification-message' => $languageService->sL('LLL:EXT:shorturls/Resources/Private/Language/locallang.xlf:notification.copied.message'),
            ];
        } else {
            $title = $languageService->sL('LLL:EXT:shorturls/Resources/Private/Language/locallang.xlf:button.create');
            $href = (string)$this->uriBuilder->buildUriFromRoute('shorturls_create', ['pageId' => $pageId, 'sourceHost' => $sourceHost]);
            $attributes = [
                'data-shorturl-confirm' => $languageService->sL('LLL:EXT:shorturls/Resources/Private/Language/locallang.xlf:confirm.create'),
            ];
        }

        $buttons = $event->getButtons();

        $shortUrlButton = $event->getButtonBar()->makeLinkButton()
            ->setHref($href)
            ->setTitle($title)
            ->setShowLabelText(true)
            ->setAttributes($attributes)
            ->setIcon($this->iconFactory->getIcon('module-urls', IconSize::SMALL));

        $buttons['left'][0][] = $shortUrlButton;
        $event->setButtons($buttons);
    }

    private function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
