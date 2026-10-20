<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestNamespacedPlugin\Model\Behavior;

use Model;
use ModelBehavior;

/**
 * Behavior in the src directory of a plugin with the CakePHP 5 layout
 */
class ModernBehavior extends ModelBehavior
{
    /**
     * Returns the name of the behavior
     *
     * @param Model $model The model
     * @return string
     */
    public function modernName(Model $model)
    {
        return 'modern behavior of ' . $model->alias;
    }
}
