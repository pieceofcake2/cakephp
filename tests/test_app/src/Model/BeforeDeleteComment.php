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
 * Model stub for beforeDelete testing
 *
 * @see #250
 * @package       Cake.Test.Case.Model
 */
class BeforeDeleteComment extends CakeTestModel
{
    public $name = 'BeforeDeleteComment';

    public $useTable = 'comments';

    public function beforeDelete($cascade = true): mixed
    {
        $db = $this->getDataSource();
        $db->delete($this, [$this->alias . '.' . $this->primaryKey => [1, 3]]);

        return true;
    }
}
