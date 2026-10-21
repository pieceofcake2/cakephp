<?php
/**
 * BlowfishAuthenticateTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under the MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Controller.Component.Auth
 * @since         CakePHP(tm) v 2.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component\Auth;

use Cake\Cache\Cache;
use Cake\Controller\Component\Auth\BlowfishAuthenticate;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Security;

App::uses('AppModel', 'Model');

/**
 * Test case for BlowfishAuthentication
 *
 * @package Cake.Test.Case.Controller.Component.Auth
 */
class BlowfishAuthenticateTest extends CakeTestCase
{
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
        $this->auth = new BlowfishAuthenticate($this->Collection, [
            'fields' => ['username' => 'user', 'password' => 'password'],
            'userModel' => 'User',
        ]);
        $password = Security::hash('password', 'blowfish');
        $User = ClassRegistry::init('User');
        $User->updateAll(['password' => $User->getDataSource()->value($password)]);
        $this->response = $this->getMock('CakeResponse');

        $hash = Security::hash('password', 'blowfish');
        $this->skipIf(!str_contains($hash, '$2a$'), 'Skipping blowfish tests as hashing is not working');
    }

    /**
     * testPluginModel method
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
        $user['password'] = Security::hash('password', 'blowfish');
        $PluginModel->save($user, false);

        $this->auth->settings['userModel'] = 'TestLegacyPlugin.TestPluginAuthUser';
        $this->auth->settings['fields']['username'] = 'username';

        $request = new CakeRequest('posts/index', false);
        $request->data = ['TestPluginAuthUser' => [
            'username' => 'gwoo',
            'password' => 'password',
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
}
