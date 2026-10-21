<?php
/**
 * SessionComponentTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller.Component
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller\Component;

use Cake\Controller\Component\SessionComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\App;
use Cake\Core\CakeObject;
use Cake\Core\Configure;
use Cake\Model\Datasource\CakeSession;
use Cake\TestSuite\CakeTestCase;

/**
 * SessionComponentTest class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class SessionComponentTest extends CakeTestCase
{
    protected static $_sessionBackup;

    /**
     * fixtures
     *
     * @var string
     */
    public $fixtures = ['core.session'];

    /**
     * test case startup
     *
     * @return void
     */
    public static function setupBeforeClass(): void
    {
        static::$_sessionBackup = Configure::read('Session');
        Configure::write('Session', [
            'defaults' => 'php',
            'timeout' => 100,
            'cookie' => 'test',
        ]);
    }

    /**
     * cleanup after test case.
     *
     * @return void
     */
    public static function teardownAfterClass(): void
    {
        Configure::write('Session', static::$_sessionBackup);
    }

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
        ]);
        $_SESSION = null;
        $this->ComponentCollection = new ComponentCollection();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakeSession::destroy();

        parent::tearDown();
    }

    /**
     * ensure that session ids don't change when request action is called.
     *
     * @return void
     */
    public function testSessionIdConsistentAcrossRequestAction(): void
    {
        $Object = new CakeObject();
        $Session = new SessionComponent($this->ComponentCollection);
        $expected = $Session->id();

        $result = $Object->requestAction('/session_test/sessionId');
        $this->assertEquals($expected, $result);

        $result = $Object->requestAction('/orange_session_test/sessionId');
        $this->assertEquals($expected, $result);
    }
}
