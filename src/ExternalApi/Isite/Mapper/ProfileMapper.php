<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\Controller\Helpers\IsiteKeyHelper;
use App\ExternalApi\Isite\Domain\Profile;
use App\ExternalApi\Isite\WrongEntityTypeException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

class ProfileMapper extends Mapper
{
    public function getDomainModel(SimpleXMLElement $isiteObject): Profile
    {
        $form = $this->getForm($isiteObject);
        $formMetaData = $this->getFormMetaData($isiteObject);
        $resultMetaData = $this->getMetaData($isiteObject);
        $projectSpace = $this->getProjectSpace($formMetaData);
        $guid = $this->getString($resultMetaData->guid);
        $key = $this->isiteKeyHelper->convertGuidToKey($guid);
        if (!$this->isProfile($resultMetaData)) {
            throw new WrongEntityTypeException(
                sprintf(
                    "iSite form with guid %s attempted to be mapped as profile, but is not a profile, is a %s",
                    $guid,
                    (string) $resultMetaData->type
                )
            );
        }
        $title = $this->getString($formMetaData->title);
        $type = $this->getString($formMetaData->type);
        $fileId = $this->getString($resultMetaData->fileId); // NOTE: This is the metadata fileId, not the form data file_id
        $image = $this->getString($formMetaData->image);
        // @codingStandardsIgnoreStart
        // Ignored PHPCS cause of snake variable fields included in the xml
        $shortSynopsis = $this->getString($formMetaData->short_synopsis);
        $longSynopsis = $this->getString($formMetaData->long_synopsis);
        $parentPid = $this->getString($formMetaData->parent_pid);
        $brandingId = $this->getString($formMetaData->branding_id);
        $imagePortrait = $this->getString($form->profile->image_portrait);
        $tagline = $this->getString($formMetaData->tagline);
        $bbcSite = $this->getString($formMetaData->bbc_site) ?: null;
        $groupSize = null;

        if (!empty($this->getString($formMetaData->group_size)) || ($this->getString($formMetaData->group_size) === '0')) {
            $groupSize = (int)$this->getString($formMetaData->group_size);
        }
        

        $keyFacts = [];
        if (!empty($form->key_facts)) {
            $facts = $form->key_facts->key_fact;
            foreach ($facts as $fact) {
                $keyFacts[] = $this->mapperFactory->createKeyFactMapper()->getDomainModel($fact);
            }
        }

        $parents = [];
        if (!empty($formMetaData->parents->parent->result)) {
            foreach ($formMetaData->parents as $parent) {
                if ($this->isPublished($parent->parent)) {
                    if ($this->isProfile($this->getMetaData($parent->parent->result))) {
                        /**
                         * iSite does not prevent you from adding other things than profiles (e.g. articles)
                         * as parents of your article. Because of course it doesn't.
                         * This filters those out
                         */
                        $parents[] = $this->mapperFactory->createProfileMapper()->getDomainModel($parent->parent->result);
                    }
                }
            }
        }

        $contentBlocks = $this->getContentBlocks($form);

        $onwardJourneyBlock = null;
        if (!empty($form->profile->onward_journeys)) {
            if ($this->isPublished($form->profile->onward_journeys)) { // Must be published
                $onwardJourneyBlock = $this->mapperFactory->createContentBlockMapper()->getDomainModel(
                    $form->profile->onward_journeys->result
                );
            }
        }
        // @codingStandardsIgnoreEnd

        return new Profile(
            $this->logger,
            $title,
            $key,
            $fileId,
            $type,
            $projectSpace,
            $parentPid,
            $shortSynopsis,
            $longSynopsis,
            $brandingId,
            $contentBlocks,
            $keyFacts,
            $image,
            $imagePortrait,
            $onwardJourneyBlock,
            $tagline,
            $parents,
            $bbcSite,
            $groupSize
        );
    }

    public function getDomainModels($contentBlocksList): array
    {
        $contentBlocksMapper = $this->mapperFactory->createContentBlockMapper();
        $contentBlocksMapper->preloadData($contentBlocksList);
        $blocks = [];
        foreach ($contentBlocksList as $block) {
            $blocks[] = $contentBlocksMapper->getDomainModel($block->result);
        }
        return $blocks;
    }

    private function isProfile(SimpleXMLElement $resultMetaData)
    {
        return (isset($resultMetaData->type) && ((string) $resultMetaData->type === 'programmes-profile'));
    }

    private function getContentBlocks(SimpleXMLElement $form): ?array
    {
        //check if module is in the data
        // @codingStandardsIgnoreStart
        if (!empty($form->profile->content_blocks)) {
            $blocks = $form->profile->content_blocks;
            $countBlocks = count($blocks);
            $countBlocksUnfetchedOrDeleted = 0;
            foreach ($blocks as $block) {
                if (isset($block->content_block) && (string) $block->content_block && strlen((string) $block->content_block)) {
                    /**
                     * Content blocks that have not been fetched look like:
                     * <content_block>urn:isite:progs-drama:programmes-content-1539623978</content_block>
                     * As do content blocks that have been deleted, annoyingly
                     */
                    $countBlocksUnfetchedOrDeleted++;
                }
            }
            if ($countBlocksUnfetchedOrDeleted === $countBlocks) {
                return null; // Content blocks have not been fetched
            }

            if (empty($blocks[0]->content_block->result)) {
                /**
                 * Content blocks that don't exist _can_ look like
                 * <content_block/>
                 * and the API needs to know the difference between "Not Fetched" and "Not existent"
                 */
                return []; // No content blocks
            }
            $contentBlocksList = [];
            foreach ($blocks as $block) {
                if ($this->isPublished($block->content_block)) { // Must be published
                    $contentBlocksList[] = $block->content_block;
                }
            }
            return $this->getDomainModels(
                $contentBlocksList
            );
        }
        // @codingStandardsIgnoreEnd
        return [];
    }
}
