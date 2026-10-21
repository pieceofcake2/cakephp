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

namespace TestApp\Model\Behavior;

use Cake\Model\Model;
use Cake\Model\ModelBehavior;

/**
 * Test5Behavior class
 *
 * @package       Cake.Test.Case.Model
 */
class Test5Behavior extends ModelBehavior
{
    public function setup(Model $model, $config = null): void
    {
        $model->bindModel(
            ['belongsTo' => ['User']],
        );
    }
}
