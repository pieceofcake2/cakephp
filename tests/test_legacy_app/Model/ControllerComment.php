<?php
/**
 * Test App ControllerComment Model
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
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ControllerComment class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ControllerComment extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'Comment';

    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'comments';

    /**
     * data property
     *
     * @var array
     */
    public $data = ['name' => 'Some Name'];

    /**
     * alias property
     *
     * @var string
     */
    public $alias = 'ControllerComment';
}
