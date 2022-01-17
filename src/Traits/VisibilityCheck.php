<?php
declare(strict_types=1);

namespace IvanoMatteo\ModelUtils\Traits;

/**
 * @method array getHidden()
 * @method array getVisible()
 */
trait VisibilityCheck
{
    public function isVisible($name):bool
    {
        if (array_search($name, $this->getHidden()) !== false) {
            return false;
        }
        return empty($this->getVisible()) || (array_search($name, $this->getVisible()) !== false);
    }
}
