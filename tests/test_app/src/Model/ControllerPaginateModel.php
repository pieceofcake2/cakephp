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
 * ControllerPaginateModel class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class ControllerPaginateModel extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'comments';

    public mixed $extra = null;
    public mixed $extraCount = null;

    /**
     * paginate method
     *
     * @param mixed $conditions
     * @param mixed $fields
     * @param mixed $order
     * @param mixed $limit
     * @param mixed $page
     * @param mixed $recursive
     * @param mixed $extra
     * @return array
     */
    public function paginate($conditions, $fields, $order, $limit, $page, $recursive, $extra): mixed
    {
        $this->extra = $extra;

        return [true];
    }

    /**
     * paginateCount
     *
     * @param mixed $conditions
     * @param mixed $recursive
     * @param mixed $extra
     * @return int
     */
    public function paginateCount($conditions, $recursive, $extra): void
    {
        $this->extraCount = $extra;
    }
}
