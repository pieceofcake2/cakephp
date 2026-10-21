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
use Exception;

/**
 * User class
 *
 * @package       Cake.Test.Case.Model
 */
class User extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'User';

    /**
     * validate property
     *
     * @var array
     */
    public $validate = ['user' => 'notBlank', 'password' => 'notBlank'];

    /**
     * beforeFind() callback used to run ContainableBehaviorTest::testLazyLoad()
     *
     * @param array $query
     * @return array|bool|null
     * @throws Exception
     */
    public function beforeFind($queryData): mixed
    {
        if (!empty($queryData['lazyLoad'])) {
            if (!isset($this->Article, $this->Comment, $this->ArticleFeatured)) {
                throw new Exception('Unavailable associations');
            }
        }

        return true;
    }
}
