<?php
declare(strict_types = 1);

namespace App\ExternalApi\Ada\Service;

use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use GuzzleHttp\Promise\FulfilledPromise;
use GuzzleHttp\Promise\PromiseInterface;

/**
 * Class AdaClassStubService
 *
 * Stub class for unit tests
 */
class AdaClassStubService extends AdaClassService
{
    public function findRelatedClassesByContainer(
        Programme $programme,
        bool $countWithinTleo = true,
        int $limit = 5
    ): PromiseInterface {
        return new FulfilledPromise([]);
    }
}
