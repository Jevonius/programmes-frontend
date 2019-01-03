<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme\SubPresenters;

use App\Ds2013\Presenters\Domain\CoreEntity\Programme\ProgrammePresenterBase;
use App\DsShared\Helpers\PlayTranslationsHelper;
use App\DsShared\Helpers\StreamableHelper;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Image;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Sub-presenter for ProgrammePresenter
 */
class ProgrammeOverlayPresenter extends ProgrammePresenterBase
{
    // @TODO see if this default_width and sizes make sense
    /** @var array */
    protected $options = [
        'show_image' => true,
        'show_overlay' => true,
        'is_lazy_loaded' => true,
        'show_standalone_cta' => false,
        'classes' => '1/4@bpb1 1/4@bpb2 1/3@bpw',
        'default_width' => 320,
        'cta_link_location_track' => 'programmeobjectlink=cta',
        'sizes' => [
            0 => '0vw',
            320 => 1 / 4,
            480 => 1 / 4,
            600 => 1 / 3,
            // @TODO confirm these are the right sizes
            1008 => '336px',
            1280 => '432px',
        ],
        'show_unavailable' => false,
    ];

    public function __construct(
        UrlGeneratorInterface $router,
        Programme $programme,
        array $options = []
    ) {
        parent::__construct($router, $programme, $options);
    }

    public function getImage(): Image
    {
        return $this->programme->getImage();
    }
}
