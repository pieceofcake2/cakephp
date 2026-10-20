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

use Cake\Core\Configure;
use Cake\Model\Model;
use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * DbAroUserTest class
 *
 * @package       Cake.Test.Case.Model
 */
class DbAroUserTest extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'AuthUser';

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'auth_users';

    /**
     * bindNode method
     *
     * @param Model|array|string $ref
     * @return void
     */
    public function bindNode($ref = null): mixed
    {
        if (Configure::read('DbAclbindMode') === 'string') {
            return 'ROOT/admins/Gandalf';
        } elseif (Configure::read('DbAclbindMode') === 'array') {
            return ['DbAroTest' => ['DbAroTest.model' => 'AuthUser', 'DbAroTest.foreign_key' => 2]];
        }

        return null;
    }
}
