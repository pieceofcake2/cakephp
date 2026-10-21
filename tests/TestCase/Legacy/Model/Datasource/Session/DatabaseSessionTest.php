<?php
/**
 * DatabaseSessionTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Model.Datasource.Session
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model\Datasource\Session;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Model\Datasource\Session\DatabaseSession;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;

/**
 * Database session test.
 *
 * @package       Cake.Test.Case.Model.Datasource.Session
 */
class DatabaseSessionTest extends CakeTestCase
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
        Configure::write('Session.handler', [
            'model' => 'SessionTestModel',
        ]);
        Configure::write('Session.timeout', 100);
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
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $this->storage = new DatabaseSession();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->storage);
        ClassRegistry::flush();

        parent::tearDown();
    }

    /**
     * test that constructor sets the right things up.
     *
     * @return void
     */
    public function testConstructionSettings(): void
    {
        ClassRegistry::flush();
        new DatabaseSession();

        $session = ClassRegistry::getObject('session');
        $this->assertInstanceOf('SessionTestModel', $session);
        $this->assertEquals('Session', $session->alias);
        $this->assertEquals('test', $session->useDbConfig);
        $this->assertEquals('sessions', $session->useTable);
    }
}
