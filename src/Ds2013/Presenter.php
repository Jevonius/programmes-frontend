<?php
declare(strict_types = 1);

namespace App\Ds2013;

use App\DsShared\BasePresenter;

/**
 * Base Class for a DS2013 Presenter
 */
abstract class Presenter extends BasePresenter
{
    final protected function getDesignSystem(): string
    {
        return '2013';
    }
}
