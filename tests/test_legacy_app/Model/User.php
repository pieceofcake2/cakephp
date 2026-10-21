<?php
/**
 * Test App User Model
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
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * User class
 *
 * @package       Cake.Test.TestApp.Model
 */
class User extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
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
     * @return bool
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
