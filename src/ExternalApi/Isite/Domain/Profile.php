<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Domain;

use App\ExternalApi\Isite\DataNotFetchedException;
use App\ExternalApi\Isite\Domain\ContentBlock\AbstractContentBlock;
use Psr\Log\LoggerInterface;

class Profile extends BaseIsiteObject
{
    /** @var string */
    private $type;

    /** @var string */
    private $longSynopsis;

    /** @var AbstractContentBlock[]|null */
    private $contentBlocks;

    /** @var KeyFact[] */
    private $keyFacts;

    /** @var AbstractContentBlock|null */
    private $onwardJourneyBlock;

    /** @var string */
    private $tagline;

    /** @var string|null */
    private $portraitImage;

    /** @var int  */
    private $groupSize;

    /** @var LoggerInterface  */
    private $logger;

    public function __construct(
        LoggerInterface $logger,
        string $title,
        string $key,
        string $fileId,
        string $type,
        string $projectSpace,
        string $parentPid,
        ?string $shortSynopsis,
        string $longSynopsis,
        string $brandingId,
        ?array $contentBlocks,
        array $keyFacts,
        string $image,
        ?string $portraitImage,
        ?AbstractContentBlock $onwardJourneyBlock,
        ?string $tagline,
        array $parents,
        ?string $bbcSite,
        ?int $groupSize = null
    ) {
        parent::__construct($title, $fileId, $projectSpace, $parentPid, $brandingId, $image, $parents, $key, $shortSynopsis, $bbcSite);
        $this->logger = $logger;
        $this->type = $type;
        $this->longSynopsis = $longSynopsis;
        $this->contentBlocks = $contentBlocks;
        $this->keyFacts = $keyFacts;
        $this->onwardJourneyBlock = $onwardJourneyBlock;
        $this->tagline = $tagline;
        $this->portraitImage = $portraitImage;
        $this->groupSize = $groupSize;

        if ($this->isIndividual()) {
            $this->setChildren([]);
            $this->setChildCount(0);
        }
    }

    public function getContentBlocks(): array
    {
        if ($this->contentBlocks === null) {
            // @TODO find a way to make this explode in the dev environment maybe, or do something
            // better than logging in a domain object
            $this->logger->warning('Profile content blocks have not been queried for yet, or a deleted content block has been included in a profile page.');
            return [];
        }

        return $this->contentBlocks;
    }

    public function getKeyFacts(): array
    {
        return $this->keyFacts;
    }

    public function getLongSynopsis(): string
    {
        return $this->longSynopsis;
    }

    public function getOnwardJourneyBlock(): ?AbstractContentBlock
    {
        return $this->onwardJourneyBlock;
    }

    public function getPortraitImage(): string
    {
        return $this->portraitImage ?: $this->image;
    }

    public function getTagline(): ?string
    {
        return $this->tagline;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getGroupSize()
    {
        return $this->groupSize;
    }

    public function isIndividual(): bool
    {
        return $this->type == 'individual';
    }

    public function isGroup(): bool
    {
        return $this->type == 'group';
    }
}
