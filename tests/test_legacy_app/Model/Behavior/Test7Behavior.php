<?php
/**
 * Test App Test7 Behavior
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.TestApp.Model.Behavior
 * @since         1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Test7Behavior class
 *
 * @package       Cake.Test.TestApp.Model.Behavior
 */
class Test7Behavior extends ModelBehavior
{
    public function setup(Model $model, $config = null): void
    {
        $model->bindModel(
            ['hasOne' => ['Attachment']],
        );
    }
}
