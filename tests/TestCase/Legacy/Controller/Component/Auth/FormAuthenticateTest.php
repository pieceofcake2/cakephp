<?php
/**
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

use Cake\Cache\Cache;
use Cake\Controller\Component\Auth\FormAuthenticate;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Security;

App::uses('AppModel', 'Model');

/**
 * Test case for FormAuthentication
 *
 * @package       Cake.Test.Case.Controller.Component.Auth
 */
class FormAuthenticateTest extends CakeTestCase
{
    /**
     * Fixtrues
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
        $this->auth = new FormAuthenticate($this->Collection, [
            'fields' => ['username' => 'user', 'password' => 'password'],
            'userModel' => 'User',
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
        $request->data = ['User' => [
            'user' => 'mariano',
            'password' => 'password',
        ]];
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
     * test a model in a plugin.
     *
     * @return void
     */
    public function testPluginModel(): void
    {
        Cache::delete('object_map', '_cake_core_');
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $PluginModel = ClassRegistry::init('TestLegacyPlugin.TestPluginAuthUser');
        $user['id'] = 1;
        $user['username'] = 'gwoo';
        $user['password'] = Security::hash(Configure::read('Security.salt') . 'cake');
        $PluginModel->save($user, false);

        $this->auth->settings['userModel'] = 'TestLegacyPlugin.TestPluginAuthUser';
        $this->auth->settings['fields']['username'] = 'username';

        $request = new CakeRequest('posts/index', false);
        $request->data = ['TestPluginAuthUser' => [
            'username' => 'gwoo',
            'password' => 'cake',
        ]];

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'username' => 'gwoo',
            'created' => '2007-03-17 01:16:23',
        ];
        $this->assertEquals(static::date(), $result['updated']);
        unset($result['updated']);
        $this->assertEquals($expected, $result);
        CakePlugin::unload();
    }

    /**
     * test password hasher settings
     *
     * @return void
     */
    public function testPasswordHasherSettings(): void
    {
        $this->auth->settings['passwordHasher'] = [
            'className' => 'Simple',
            'hashType' => 'md5',
        ];

        $passwordHasher = $this->auth->passwordHasher();
        $result = $passwordHasher->config();
        $this->assertEquals('md5', $result['hashType']);

        $hash = Security::hash('mypass', 'md5', true);
        $User = ClassRegistry::init('User');
        $User->updateAll(
            ['password' => $User->getDataSource()->value($hash)],
            ['User.user' => 'mariano'],
        );

        $request = new CakeRequest('posts/index', false);
        $request->data = ['User' => [
            'user' => 'mariano',
            'password' => 'mypass',
        ]];

        $result = $this->auth->authenticate($request, $this->response);
        $expected = [
            'id' => 1,
            'user' => 'mariano',
            'created' => '2007-03-17 01:16:23',
            'updated' => '2007-03-17 01:18:31',
        ];
        $this->assertEquals($expected, $result);

        $this->auth = new FormAuthenticate($this->Collection, [
            'fields' => ['username' => 'user', 'password' => 'password'],
            'userModel' => 'User',
        ]);
        $this->auth->settings['passwordHasher'] = [
            'className' => 'Simple',
            'hashType' => 'sha1',
        ];
        $this->assertFalse($this->auth->authenticate($request, $this->response));
    }
}
