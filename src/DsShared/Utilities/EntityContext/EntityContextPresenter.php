<?php
declare(strict_types = 1);
namespace App\DsShared\Utilities\EntityContext;

use App\DsShared\Presenter;
use App\Exception\InvalidOptionException;
use BBC\ProgrammesPagesService\Domain\Entity\CoreEntity;

class EntityContextPresenter extends Presenter
{
    protected $options = [
        'include_self' => true,
        'is_tabbable' => false,
    ];

    /** @var CoreEntity[] */
    private $ancestry;

    public function __construct(
        CoreEntity $entity,
        array $options = []
    ) {
        parent::__construct($options);
        $this->ancestry = array_reverse($entity->getAncestry());

        if (!$this->getOption('include_self')) {
            array_pop($this->ancestry);
        }
    }

    public function getAncestry(): array
    {
        return $this->ancestry;
    }

    protected function validateOptions(array $options): void
    {
        parent::validateOptions($options);

        if (!is_bool($this->getOption('include_self'))) {
            throw new InvalidOptionException("include_self must a bool");
        }
    }
}
