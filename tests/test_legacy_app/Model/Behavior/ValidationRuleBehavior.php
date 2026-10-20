<?php
/**
 * Test App ValidationRule Behavior
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
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Behavior for testing validation rules.
 */
class ValidationRuleBehavior extends ModelBehavior
{
    public function setup(Model $Model, $config = []): void
    {
        $this->settings[$Model->alias] = $config;
    }

    public function beforeValidate(Model $Model, $options = []): void
    {
        $fields = $this->settings[$Model->alias]['fields'];
        foreach ($fields as $field) {
            $Model->whitelist[] = $field;
        }
    }
}
