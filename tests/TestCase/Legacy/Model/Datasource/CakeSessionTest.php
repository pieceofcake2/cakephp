<?php
/**
 * SessionTest file
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
 * @package       Cake.Test.Case.Model.Datasource
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model\Datasource;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Model\Datasource\CakeSession;
use Cake\TestSuite\CakeTestCase;

/**
 * TestCakeSession
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class TestCakeSession extends CakeSession
{
    public static function setUserAgent($value): void
    {
        static::$_userAgent = $value;
    }

    public static function setHost($host): void
    {
        static::_setHost($host);
    }
}

/**
 * CakeSessionTest class
 *
 * @package       Cake.Test.Case.Model.Datasource
 */
class CakeSessionTest extends CakeTestCase
{
    protected static $_gcDivisor;

    /**
     * Fixtures used in the SessionTest
     *
     * @var array
     */
    public $fixtures = ['core.session'];

    /**
     * setup before class.
     *
     * @return void
     */
    public static function setupBeforeClass(): void
    {
        // Make sure garbage colector will be called
        static::$_gcDivisor = ini_get('session.gc_divisor');
        ini_set('session.gc_divisor', '1');
    }

    /**
     * teardown after class
     *
     * @return void
     */
    public static function teardownAfterClass(): void
    {
        // Revert to the default setting
        ini_set('session.gc_divisor', static::$_gcDivisor);
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
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'Model/Datasource/Session' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Session' . DS],
        ]);
        Configure::write('Session', [
            'defaults' => 'php',
            'cookie' => 'cakephp',
            'timeout' => 120,
            'cookieTimeout' => 120,
            'ini' => [],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (TestCakeSession::started()) {
            session_write_close();
        }
        unset($_SESSION);

        parent::tearDown();
    }

    /**
     * test using a handler from app/Model/Datasource/Session.
     *
     * @return void
     */
    public function testUsingAppLibsHandler(): void
    {
        App::build([
            'Model/Datasource/Session' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Session' . DS,
            ],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        Configure::write('Session', [
            'defaults' => 'cake',
            'handler' => [
                'engine' => 'TestAppLibSession',
            ],
        ]);

        TestCakeSession::start();
        $this->assertTrue(TestCakeSession::started());

        TestCakeSession::destroy();
        $this->assertFalse(TestCakeSession::started());

        App::build();
    }

    /**
     * test using a handler from a plugin.
     *
     * @return void
     */
    public function testUsingPluginHandler(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        Configure::write('Session', [
            'defaults' => 'cake',
            'handler' => [
                'engine' => 'TestLegacyPlugin.TestPluginSession',
            ],
        ]);

        TestCakeSession::start();
        $this->assertTrue(TestCakeSession::started());

        TestCakeSession::destroy();
        $this->assertFalse(TestCakeSession::started());

        App::build();
    }

    /**
     * testReadAndWriteWithCacheStorage method
     *
     * @return void
     */
    public function testReadAndWriteWithCacheStorage(): void
    {
        Configure::write('Session.defaults', 'cache');
        Configure::write('Session.handler.engine', 'TestCacheSession');

        TestCakeSession::init();
        TestCakeSession::destroy();

        TestCakeSession::write('SessionTestCase', 0);
        $this->assertEquals(0, TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', '0');
        $this->assertEquals('0', TestCakeSession::read('SessionTestCase'));
        $this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

        TestCakeSession::write('SessionTestCase', false);
        $this->assertFalse(TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', null);
        $this->assertEquals(null, TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', 'This is a Test');
        $this->assertEquals('This is a Test', TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', 'This is a Test');
        TestCakeSession::write('SessionTestCase', 'This was updated');
        $this->assertEquals('This was updated', TestCakeSession::read('SessionTestCase'));

        TestCakeSession::destroy();
        $this->assertNull(TestCakeSession::read('SessionTestCase'));
    }

    /**
     * testReadAndWriteWithDatabaseStorage method
     *
     * @return void
     */
    public function testReadAndWriteWithDatabaseStorage(): void
    {
        Configure::write('Session.defaults', 'database');
        Configure::write('Session.handler.engine', 'TestDatabaseSession');
        Configure::write('Session.handler.table', 'sessions');
        Configure::write('Session.handler.model', 'Session');
        Configure::write('Session.handler.database', 'test');

        TestCakeSession::init();
        $this->assertNull(TestCakeSession::id());

        TestCakeSession::start();
        $expected = session_id();
        $this->assertEquals($expected, TestCakeSession::id());

        TestCakeSession::renew();
        $this->assertFalse($expected === TestCakeSession::id());

        $expected = session_id();
        $this->assertEquals($expected, TestCakeSession::id());

        TestCakeSession::write('SessionTestCase', 0);
        $this->assertEquals(0, TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', '0');
        $this->assertEquals('0', TestCakeSession::read('SessionTestCase'));
        $this->assertFalse(TestCakeSession::read('SessionTestCase') === 0);

        TestCakeSession::write('SessionTestCase', false);
        $this->assertFalse(TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', null);
        $this->assertEquals(null, TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', 'This is a Test');
        $this->assertEquals('This is a Test', TestCakeSession::read('SessionTestCase'));

        TestCakeSession::write('SessionTestCase', 'Some additional data');
        $this->assertEquals('Some additional data', TestCakeSession::read('SessionTestCase'));

        TestCakeSession::destroy();
        $this->assertNull(TestCakeSession::read('SessionTestCase'));

        Configure::write('Session', [
            'defaults' => 'php',
        ]);
        TestCakeSession::init();
    }
}
