<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Tests\Unit\Controller;

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\ShortUrlService;
use UEBERBIT\Shorturls\Controller\ShortUrlController;

final class ShortUrlControllerTest extends TestCase
{
    private ShortUrlController $subject;
    private ConnectionPool&MockObject $connectionPool;
    private UriBuilder&MockObject $uriBuilder;
    private ShortUrlService&MockObject $shortUrlService;
    private CacheManager&MockObject $cacheManager;
    private SiteFinder&MockObject $siteFinder;
    private RedirectCacheService&MockObject $redirectCacheService;
    private FlashMessageService&MockObject $flashMessageService;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->uriBuilder = $this->createMock(UriBuilder::class);
        $this->shortUrlService = $this->createMock(ShortUrlService::class);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->siteFinder = $this->createMock(SiteFinder::class);
        $this->redirectCacheService = $this->createMock(RedirectCacheService::class);
        $this->flashMessageService = $this->createMock(FlashMessageService::class);

        $this->subject = new ShortUrlController(
            $this->connectionPool,
            $this->uriBuilder,
            $this->shortUrlService,
            $this->cacheManager,
            $this->siteFinder,
            $this->redirectCacheService,
            $this->flashMessageService
        );
    }

    public function testCreateActionRedirectsToReturnUrl(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageId' => '0',
            'returnUrl' => '/return',
        ]);

        $response = $this->subject->createAction($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/return', $response->getHeaderLine('Location'));
    }

    public static function sourceHostDataProvider(): array
    {
        return [
            'site with host' => [new Site('main', 1, ['base' => 'https://example.com/']), 'example.com'],
            'site without host' => [new Site('main', 1, ['base' => '/']), '*'],
            'no site found' => [null, '*'],
        ];
    }

    #[DataProvider('sourceHostDataProvider')]
    public function testCreateActionResolvesSourceHostFromSite(?Site $site, string $expectedSourceHost): void
    {
        if ($site === null) {
            $this->siteFinder->method('getSiteByPageId')->willThrowException(new SiteNotFoundException('No site found', 1));
        } else {
            $this->siteFinder->method('getSiteByPageId')->willReturn($site);
        }

        // Record named parameters of the "already exists" query; report an existing redirect
        // so the action stops before generating a new short URL.
        $namedParameters = [];
        $queryBuilder = $this->createMock(QueryBuilder::class);
        foreach (['count', 'from', 'where'] as $method) {
            $queryBuilder->method($method)->willReturnSelf();
        }
        $queryBuilder->method('expr')->willReturn($this->createMock(ExpressionBuilder::class));
        $queryBuilder->method('createNamedParameter')->willReturnCallback(
            static function (mixed $value) use (&$namedParameters): string {
                $namedParameters[] = $value;
                return ':dcValue' . count($namedParameters);
            }
        );
        $result = $this->createMock(Result::class);
        $result->method('fetchOne')->willReturn(1);
        $queryBuilder->method('executeQuery')->willReturn($result);
        $this->connectionPool->method('getQueryBuilderForTable')->with('sys_redirect')->willReturn($queryBuilder);

        $this->flashMessageService->method('getMessageQueueByIdentifier')
            ->willReturn($this->createMock(FlashMessageQueue::class));
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);

        $this->shortUrlService->expects(self::never())->method('generateUniqueShortUrlPath');

        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageId' => '42',
            'returnUrl' => '/return',
        ]);

        $this->subject->createAction($request);

        self::assertSame($expectedSourceHost, $namedParameters[0]);
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['LANG']);
        parent::tearDown();
    }
}
