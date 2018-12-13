<?php
declare(strict_types = 1);

namespace App\ExternalApi\Isite\Mapper;

use App\Controller\Helpers\IsiteKeyHelper;
use App\ExternalApi\Exception\ParseException;
use Psr\Log\LoggerInterface;
use SimpleXMLElement;

/**
 *  This is the base mapper for the iSite which provides the basic
 *  functionality such as creating new instances of the same model
 *  setting and getting values etc.
 */
abstract class Mapper
{
    /** @var IsiteKeyHelper */
    protected $isiteKeyHelper;

    /** @var MapperFactory */
    protected $mapperFactory;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(MapperFactory $mapperFactory, IsiteKeyHelper $isiteKeyHelper, LoggerInterface $logger)
    {
        $this->isiteKeyHelper = $isiteKeyHelper;
        $this->mapperFactory = $mapperFactory;
        $this->logger = $logger;
    }

    abstract public function getDomainModel(SimpleXMLElement $isiteObject);

    protected function getForm(SimpleXMLElement $isiteObject): SimpleXMLElement
    {
        if (empty($isiteObject->document->form)) {
            throw new ParseException('Invalid iSite XML document');
        }
        return $isiteObject->document->form;
    }

    /**
     * Gets the metadata directly from the header
     * @param  SimpleXMLElement $isiteObject
     * @return SimpleXMLElement
     */
    protected function getMetaData(SimpleXMLElement $isiteObject): SimpleXMLElement
    {
        return $isiteObject->metadata;
    }

    /**
     * Method to get the metadata form isite document
     * @param  SimpleXMLElement $isiteObject
     * @return SimpleXMLElement|null
     */
    protected function getFormMetaData(SimpleXMLElement $isiteObject): ?SimpleXMLElement
    {
        return $this->getForm($isiteObject)->metadata;
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

    protected function getString(?SimpleXMLElement $val): ?string
    {
        if (empty($val)) {
            return null;
        }
        $val = (string) $val;
        return trim($val);
    }

    protected function isPublished(SimpleXMLElement $context): bool
    {
        return isset($context->result->metadata->guid);
    }
}
