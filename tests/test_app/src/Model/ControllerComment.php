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

namespace TestApp\Model;

use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * ControllerComment class
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerComment extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Comment';

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'comments';

    /**
     * data property
     *
     * @var array|false|null
     */
    public $data = ['name' => 'Some Name'];

    /**
     * alias property
     *
     * @var string|null
     */
    public $alias = 'ControllerComment';
}
