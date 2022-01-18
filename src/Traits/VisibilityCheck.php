<?php

declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

/**
 * @method array getHidden()
 * @method array getVisible()
 */
trait VisibilityCheck
{
    public function isVisible($name): bool
    {
        if (in_array($name, $this->getHidden())) {
            return false;
        }

        return empty($this->getVisible()) || (in_array($name, $this->getVisible()));
    }
}
