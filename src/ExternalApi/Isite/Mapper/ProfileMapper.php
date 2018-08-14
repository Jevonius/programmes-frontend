<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\ExternalApi\Exception\ParseException;
use App\ExternalApi\Isite\Domain\Profile;
use SimpleXMLElement;

class ProfileMapper extends Mapper
{
    public function getDomainModel(SimpleXMLElement $isiteObject): Profile
    {
        $form = $this->getForm($isiteObject);
        $formMetaData = $form->metadata;
        $projectSpace = $this->getProjectSpace($formMetaData);
        $key = $this->isiteKeyHelper->convertGuidToKey($this->getString($this->getMetaData($isiteObject)->guid));
        $title = $this->getString($formMetaData->title);
        $type = $this->getString($formMetaData->type);
        $fileId = $this->getString($this->getMetaData($isiteObject)->fileId); // NOTE: This is the metadata fileId, not the form data file_id
        $image = $this->getString($formMetaData->image);
        // @codingStandardsIgnoreStart
        // Ignored PHPCS cause of snake variable fields included in the xml
        $shortSynopsis = $this->getString($formMetaData->short_synopsis);
        $longSynopsis = $this->getString($formMetaData->long_synopsis);
        $parentPid = $this->getString($formMetaData->parent_pid);
        $brandingId = $this->getString($formMetaData->branding_id);
        $imagePortrait = $this->getString($form->profile->image_portrait);

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
                $parents[] = $this->mapperFactory->createProfileMapper()->getDomainModel($parent->parent->result);
            }
        }

        $contentBlocks = [];
        //check if module is in the data
        if (!empty($form->profile->content_blocks)) {
            $blocks = $form->profile->content_blocks;
            if (empty($blocks[0]->content_block->result)) {
                $contentBlocks = null; // Content blocks have not been fetched
            } else {
                foreach ($blocks as $block) {
                    if (!empty($block->content_block->result->metadata->guid)) { // Must be published
                        $contentBlocks[] = $this->mapperFactory->createContentBlockMapper()->getDomainModel(
                            $block->content_block->result
                        );
                    }
                }
            }
        }
        // @codingStandardsIgnoreEnd

        $onwardJourneyBlock = null;
        if (!empty($form->profile->onward_journeys)) {
            $onwardJourneyBlock = $this->mapperFactory->createContentBlockMapper()->getDomainModel(
                $form->profile->onward_journeys->result
            );
        }

        return new Profile($title, $key, $fileId, $type, $projectSpace, $parentPid, $shortSynopsis, $longSynopsis, $brandingId, $contentBlocks, $keyFacts, $image, $imagePortrait, $onwardJourneyBlock, $parents);
    }

    protected function getProjectSpace(SimpleXMLElement $form): string
    {
        $namespaces = $form->getNamespaces();
        $namespace = reset($namespaces);
        $matches = [];
        preg_match('{https://production(?:\.int|\.test|\.stage|\.live)?\.bbc\.co\.uk/isite2/project/([^/]+)/}', $namespace, $matches);
        if (empty($matches[1])) {
            throw new ParseException('iSite XML does not specify project space and is therefore invalid');
        }
        return $matches[1];
    }
}