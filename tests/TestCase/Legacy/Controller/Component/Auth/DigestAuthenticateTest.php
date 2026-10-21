<?php
/**
 * DigestAuthenticateTest file
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

use Cake\Controller\Component\Auth\DigestAuthenticate;
use Cake\Core\App;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;

App::uses('AppModel', 'Model');

/**
 * Test case for DigestAuthentication
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class DigestAuthenticateTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.user', 'core.auth_user'];

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
        $this->server = $_SERVER;
        $this->auth = new DigestAuthenticate($this->Collection, [
            'fields' => ['username' => 'user', 'password' => 'password'],
            'userModel' => 'User',
            'realm' => 'localhost',
            'nonce' => 123,
            'opaque' => '123abc',
        ]);

        $password = DigestAuthenticate::password('mariano', 'cake', 'localhost');
        $User = ClassRegistry::init('User');
        $User->updateAll(['password' => $User->getDataSource()->value($password)]);

        $_SERVER['REQUEST_METHOD'] = 'GET';
        $this->response = $this->getMock('CakeResponse');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        $_SERVER = $this->server;

        parent::tearDown();
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

        $_SERVER['PHP_AUTH_DIGEST'] = <<<DIGEST
Digest username="mariano",
realm="localhost",
nonce="123",
uri="/dir/index.html",
qop=auth,
nc=1,
cnonce="123",
response="06b257a54befa2ddfb9bfa134224aa29",
opaque="123abc"
DIGEST;

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
