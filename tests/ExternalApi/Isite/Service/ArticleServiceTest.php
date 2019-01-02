<?php
declare(strict_types = 1);

namespace Tests\App\ExternalApi\Isite\Service;

use App\ExternalApi\Client\HttpApiClientFactory;
use App\ExternalApi\Client\HttpApiMultiClient;
use App\ExternalApi\Isite\Domain\Article;
use App\ExternalApi\Isite\IsiteFeedResponseHandler;
use App\ExternalApi\Isite\IsiteResult;
use App\ExternalApi\Isite\Service\ArticleService;
use BBC\ProgrammesCachingLibrary\CacheInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Response;
use PHPUnit\Framework\TestCase;

class ArticleServiceTest extends TestCase
{
    public function testGetHttpApiMultiClientIsCalledWithCorrectNumberOfUrlsAndArticles()
    {
        $articleA = $this->createMock(Article::class);
        $articleA->method('getFileId')->willReturn('fileIdA');
        $articleB = $this->createMock(Article::class);
        $articleB->method('getFileId')->willReturn('fileIdB');
        $articleC = $this->createMock(Article::class);
        $articleC->method('getFileId')->willReturn('fileIdC');
        $urls = [
            'baseurl/search?q=%7B%22namespaces%22%3A%7B%22article%22%3A%22https%3A%5C%2F%5C%2Fproduction.bbc.co.uk%5C%2Fisite2%5C%2Fproject%5C%2Fproject-space%5C%2Fprogrammes-article%22%7D%2C%22project%22%3A%22project-space%22%2C%22depth%22%3A0%2C%22sort%22%3A%5B%7B%22elementPath%22%3A%22%5C%2Farticle%3Aform%5C%2Farticle%3Ametadata%5C%2Farticle%3Aposition%22%2C%22type%22%3A%22numeric%22%2C%22direction%22%3A%22asc%22%7D%2C%7B%22elementPath%22%3A%22%5C%2Farticle%3Aform%5C%2Farticle%3Ametadata%5C%2Farticle%3Atitle%22%2C%22direction%22%3A%22asc%22%7D%5D%2C%22page%22%3A%221%22%2C%22pageSize%22%3A%2248%22%2C%22query%22%3A%5B%22article%3Aparent%22%2C%22%3D%22%2C%22urn%3Aisite%3Aproject-space%3AfileIdA%22%5D%7D',
            'baseurl/search?q=%7B%22namespaces%22%3A%7B%22article%22%3A%22https%3A%5C%2F%5C%2Fproduction.bbc.co.uk%5C%2Fisite2%5C%2Fproject%5C%2Fproject-space%5C%2Fprogrammes-article%22%7D%2C%22project%22%3A%22project-space%22%2C%22depth%22%3A0%2C%22sort%22%3A%5B%7B%22elementPath%22%3A%22%5C%2Farticle%3Aform%5C%2Farticle%3Ametadata%5C%2Farticle%3Aposition%22%2C%22type%22%3A%22numeric%22%2C%22direction%22%3A%22asc%22%7D%2C%7B%22elementPath%22%3A%22%5C%2Farticle%3Aform%5C%2Farticle%3Ametadata%5C%2Farticle%3Atitle%22%2C%22direction%22%3A%22asc%22%7D%5D%2C%22page%22%3A%221%22%2C%22pageSize%22%3A%2248%22%2C%22query%22%3A%5B%22article%3Aparent%22%2C%22%3D%22%2C%22urn%3Aisite%3Aproject-space%3AfileIdB%22%5D%7D',
            'baseurl/search?q=%7B%22namespaces%22%3A%7B%22article%22%3A%22https%3A%5C%2F%5C%2Fproduction.bbc.co.uk%5C%2Fisite2%5C%2Fproject%5C%2Fproject-space%5C%2Fprogrammes-article%22%7D%2C%22project%22%3A%22project-space%22%2C%22depth%22%3A0%2C%22sort%22%3A%5B%7B%22elementPath%22%3A%22%5C%2Farticle%3Aform%5C%2Farticle%3Ametadata%5C%2Farticle%3Aposition%22%2C%22type%22%3A%22numeric%22%2C%22direction%22%3A%22asc%22%7D%2C%7B%22elementPath%22%3A%22%5C%2Farticle%3Aform%5C%2Farticle%3Ametadata%5C%2Farticle%3Atitle%22%2C%22direction%22%3A%22asc%22%7D%5D%2C%22page%22%3A%221%22%2C%22pageSize%22%3A%2248%22%2C%22query%22%3A%5B%22article%3Aparent%22%2C%22%3D%22%2C%22urn%3Aisite%3Aproject-space%3AfileIdC%22%5D%7D',
        ];
        $mockClient = $this->createMock(HttpApiMultiClient::class);
        $mockClient->expects($this->once())
            ->method('makeCachedPromise')
            ->willReturn(new Promise());

        $mockHttpApiClientFactory = $this->createMock(HttpApiClientFactory::class);
        $mockHttpApiClientFactory->expects($this->once())
            ->method('keyHelper')
            ->willReturn('cacheKey');
        $mockHttpApiClientFactory->expects($this->once())
            ->method('getHttpApiMultiClient')
            ->with('cacheKey', $urls, function () {
            }, [[$articleA, $articleB, $articleC]], [], CacheInterface::NORMAL, CacheInterface::NONE, ['timeout' => 15, 'connect_timeout' => 10])
            ->willReturn($mockClient);
        $service = new ArticleService('baseurl', $mockHttpApiClientFactory, $this->createMock(IsiteFeedResponseHandler::class));
        $service->setChildrenOn([$articleA, $articleB, $articleC], 'project-space');
    }

    public function testResponseHandlerOfGetChildrenOfArticlesResponses()
    {
        $responseA = $this->createMock(Response::class);
        $responseB = $this->createMock(Response::class);
        $iSiteResultA = $this->createMock(IsiteResult::class);
        $iSiteResultB = $this->createMock(IsiteResult::class);
        $mockResponseHandler = $this->createMock(IsiteFeedResponseHandler::class);
        $mockResponseHandler->expects($this->exactly(2))
            ->method('getIsiteResult')
            ->withConsecutive($responseA, $responseB)
            ->willReturnOnConsecutiveCalls($iSiteResultA, $iSiteResultB);
        $service = new ArticleService('baseurl', $this->createMock(HttpApiClientFactory::class), $mockResponseHandler);
        $service->parseResponses([$responseA, $responseB]);
    }

    public function testResponseHandlerOfGetArticlesByProgramme()
    {
        $response = $this->createMock(Response::class);
        $iSiteResult = $this->createMock(IsiteResult::class);
        $mockResponseHandler = $this->createMock(IsiteFeedResponseHandler::class);
        $mockResponseHandler->expects($this->once())
            ->method('getIsiteResult')
            ->with($response)
            ->willReturn($iSiteResult);
        $service = new ArticleService('baseurl', $this->createMock(HttpApiClientFactory::class), $mockResponseHandler);
        $this->assertEquals($iSiteResult, $service->parseResponse([$response]));
    }
}
