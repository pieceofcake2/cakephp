<?php
/**
 * Test App First Behavior
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
 * FirstBehavior
 */
class FirstBehavior extends ModelBehavior
{
    public function beforeFind(Model $model, $query = []): mixed
    {
        $model->called[] = static::class;

        return $query;
    }
}
