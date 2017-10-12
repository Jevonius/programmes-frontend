<?php
declare(strict_types = 1);

namespace App\ValueObject;

use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use BBC\ProgrammesPagesService\Domain\Entity\Network;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\Service;
use BBC\ProgrammesPagesService\Domain\ValueObject\Pid;

class MetaContext
{
    /** @var string */
    private $description = '';

    /** @var Image */
    private $image;

    /** @var bool */
    private $isRadio = false;

    /** @var string */
    private $titlePrefix = '';

    /** @var string */
    private $canonicalUrl = '';

    /** @var bool */
    private $showAdverts = false;

    /** @var string */
    private $projectSpace = 'none';

    /** @var CoreEntity|Network */
    private $context;

    public function __construct($context = null, string $canonicalUrl = '')
    {
        $this->canonicalUrl = $canonicalUrl;
        $this->context = $context;

        if ($context instanceof CoreEntity) {
            $this->description = $context->getShortSynopsis();
            $this->image = $context->getImage();
            $this->isRadio = $context->isRadio();
            $this->titlePrefix = $this->coreEntityTitlePrefix($context);
            $this->projectSpace = $context->getOption('projectId') ?? 'none';

            if ($context->getNetwork()) {
                $this->showAdverts = $context->getNetwork()->isInternational();
            }
        } elseif ($context instanceof Service) {
            $this->isRadio = $context->isRadio();
            $this->titlePrefix = $context->getName();

            if ($context->getNetwork()) {
                $this->image = $context->getNetwork()->getImage();
            }
        }

        if (is_null($this->image)) {
            $this->image = new Image(
                new Pid('p01tqv8z'),
                'bbc_640x360.png',
                'BBC Blocks for /programmes',
                'BBC Blocks for /programmes',
                'standard',
                'png'
            );
        }
    }

    public function canonicalUrl(): string
    {
        return $this->canonicalUrl;
    }

    public function context()
    {
        return $this->context;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function getBBCFacebookPageIds(): string
    {
        return implode(',', $this->bbcFacebookPageIds());
    }

    public function hasSchemaType(): bool
    {
        return $this->context instanceof Programme;
    }

    public function image(): Image
    {
        return $this->image;
    }

    public function isRadio(): bool
    {
        return $this->isRadio;
    }

    public function titlePrefix(): string
    {
        return $this->titlePrefix;
    }

    public function getProjectSpace(): string
    {
        return $this->projectSpace;
    }

    public function showAdverts(): bool
    {
        return $this->showAdverts;
    }

    /**
     * @return int[]
     */
    private function bbcFacebookPageIds(): array
    {
        return [
            6025943146,
            7397061762,
            7519460786,
            7833211321,
            8244244903,
            8251776107,
            8585725981,
            21750735380,
            80758950658,
            125309456546,
            130593816777,
            154344434967,
            228735667216,
            260212261199,
            260967092113,
            294662213128,
            295830058648,
            304314573046,
            401538510458,
            107909022566650,
            118883634811868,
            129044383774217,
            156060587793370,
            156400551056385,
            163571453661989,
            168895963122035,
            185246968166196,
            193022337414607,
            193435954068976,
            194575130577797,
            215504865453262,
            239931389545417,
            273726292719943,
            283348121682053,
            286567251709437,
            292291897588734,
            310719525611571,
            317278538359186,
            413132078795966,
            470911516262605,
            512423982152360,
            647687225371774,
            658551547588605,
            742734325867560,
            944295152308991,
            958681370814419,
            1086451581439050,
            1143803202301544,
            1159932557403143,
            1392506827668140,
            1411916919051820,
            1436581493296600,
            1470145583204820,
            1477945425811579,
            1659215157653827,
            1731770190373618,
        ];
    }

    private function coreEntityTitlePrefix(CoreEntity $coreEntity): string
    {
        $prefix = '';
        if ($coreEntity->getTleo()->getNetwork()) {
            $prefix = $coreEntity->getTleo()->getNetwork()->getName() . ' - ';
        }

        $longerTitleParts = [];
        foreach (array_reverse($coreEntity->getAncestry()) as $ancestor) {
            $longerTitleParts[] = $ancestor->getTitle();
        }

        $prefix .= implode(', ', $longerTitleParts);
        return $prefix;
    }
}
