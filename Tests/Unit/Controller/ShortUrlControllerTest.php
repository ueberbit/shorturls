<?php

declare(strict_types=1);

namespace UEBERBIT\Shorturls\Tests\Unit\Controller;

use UEBERBIT\Shorturls\Controller\ShortUrlController;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RedirectResponse;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\ShortUrlService;

final class ShortUrlControllerTest extends TestCase
{
    private ShortUrlController $subject;
    private ConnectionPool&MockObject $connectionPool;
    private UriBuilder&MockObject $uriBuilder;
    private ShortUrlService&MockObject $shortUrlService;
    private CacheManager&MockObject $cacheManager;
    private SiteFinder&MockObject $siteFinder;
    private RedirectCacheService&MockObject $redirectCacheService;

    protected function setUp(): void
    {
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->uriBuilder = $this->createMock(UriBuilder::class);
        $this->shortUrlService = $this->createMock(ShortUrlService::class);
        $this->cacheManager = $this->createMock(CacheManager::class);
        $this->siteFinder = $this->createMock(SiteFinder::class);
        $this->redirectCacheService = $this->createMock(RedirectCacheService::class);

        $this->subject = new ShortUrlController(
            $this->connectionPool,
            $this->uriBuilder,
            $this->shortUrlService,
            $this->cacheManager,
            $this->siteFinder,
            $this->redirectCacheService
        );
    }

    public function testCreateActionRedirectsToReturnUrl(): void
    {
        $request = $this->createMock(ServerRequestInterface::class);
        $request->method('getQueryParams')->willReturn([
            'pageId' => '0',
            'returnUrl' => '/return'
        ]);

        $response = $this->subject->createAction($request);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertEquals('/return', $response->getHeaderLine('Location'));
    }
}
