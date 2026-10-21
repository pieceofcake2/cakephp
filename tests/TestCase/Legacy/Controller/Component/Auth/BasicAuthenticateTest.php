<?php
/**
 * BasicAuthenticateTest file
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
 * @package       Cake.Test.Case.Controller.Component.Auth
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component\Auth;

use Cake\Controller\Component\Auth\BasicAuthenticate;
use Cake\Core\App;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Security;

App::uses('AppModel', 'Model');

/**
 * Test case for BasicAuthentication
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class BasicAuthenticateTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.user', 'core.auth_user', 'core.article'];

    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $this->Collection = $this->getMock('ComponentCollection');
        $this->auth = new BasicAuthenticate($this->Collection, [
            'fields' => ['username' => 'user', 'password' => 'password'],
            'userModel' => 'User',
            'realm' => 'localhost',
            'recursive' => 0,
        ]);

        $password = Security::hash('password', null, true);
        $User = ClassRegistry::init('User');
        $User->updateAll(['password' => $User->getDataSource()->value($password)]);
        $this->response = $this->getMock('CakeResponse');
    }

    /**
     * test authenticate success
     *
     * @return void
     */
    public function testAuthenticateSuccess(): void
    {
        $request = new CakeRequest('posts/index', false);
        $request->addParams(['pass' => [], 'named' => []]);

        $_SERVER['PHP_AUTH_USER'] = 'mariano';
        $_SERVER['PHP_AUTH_PW'] = 'password';

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'user' => 'mariano',
            'created' => '2007-03-17 01:16:23',
            'updated' => '2007-03-17 01:18:31',
        ];
        $this->assertEquals($expected, $result);
    }

    /**
     * testAuthenticateWithBlowfish
     *
     * @return void
     */
    public function testAuthenticateWithBlowfish(): void
    {
        $hash = Security::hash('password', 'blowfish');
        $this->skipIf(!str_contains($hash, '$2a$'), 'Skipping blowfish tests as hashing is not working');

        $request = new CakeRequest('posts/index', false);
        $request->addParams(['pass' => [], 'named' => []]);

        $_SERVER['PHP_AUTH_USER'] = 'mariano';
        $_SERVER['PHP_AUTH_PW'] = 'password';

        $User = ClassRegistry::init('User');
        $User->updateAll(
            ['password' => $User->getDataSource()->value($hash)],
            ['User.user' => 'mariano'],
        );

        $this->auth->settings['passwordHasher'] = 'Blowfish';

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'user' => 'mariano',
            'created' => '2007-03-17 01:16:23',
            'updated' => '2007-03-17 01:18:31',
        ];
        $this->assertEquals($expected, $result);
    }
}
