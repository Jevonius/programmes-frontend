<?php
declare(strict_types = 1);
namespace App\Ds2013\Presenters\Domain\CoreEntity\Programme;

use App\Ds2013\Presenter;
use BBC\ProgrammesPagesService\Domain\Entity\Episode;
use BBC\ProgrammesPagesService\Domain\Entity\Programme;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeContainer;
use BBC\ProgrammesPagesService\Domain\Entity\ProgrammeItem;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * This contains common functions for all programme presenter sub-classes
 */
abstract class ProgrammePresenterBase extends Presenter
{
    /** @var Programme */
    protected $programme;

    /** @var UrlGeneratorInterface */
    protected $router;

    public function __construct(
        UrlGeneratorInterface $router,
        Programme $programme,
        array $options = []
    ) {
        parent::__construct($options);
        $this->router = $router;
        $this->programme = $programme;
    }

    public function getProgramme(): Programme
    {
        return $this->programme;
    }

    public function hasFutureAvailability(): bool
    {
        return $this->programme instanceof ProgrammeItem && $this->programme->hasFutureAvailability();
    }

    public function isAvailable(): bool
    {
        return ($this->programme instanceof ProgrammeItem && $this->programme->hasPlayableDestination());
    }

    public function isContainer(): bool
    {
        return ($this->programme instanceof ProgrammeContainer);
    }

    public function isEpisode(): bool
    {
        return ($this->programme instanceof Episode);
    }
}
