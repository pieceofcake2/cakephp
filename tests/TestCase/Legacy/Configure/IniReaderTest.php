<?php
/**
 * IniReaderTest
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
 * @package       Cake.Test.Case.Configure
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Configure;

use Cake\Configure\IniReader;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;

/**
 * IniReaderTest
 *
 * @package       Cake.Test.Case.Configure
 */
class IniReaderTest extends CakeTestCase
{
    /**
     * setup
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->path = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Config' . DS;
    }

    /**
     * Test reading from plugins.
     *
     * @return void
     */
    public function testReadPluginValue(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $reader = new IniReader($this->path);
        $result = $reader->read('TestLegacyPlugin.nested');

        $this->assertTrue(isset($result['database']['db']['username']));
        $this->assertEquals('bar', $result['database']['db']['username']);
        $this->assertFalse(isset($result['database.db.username']));
        $this->assertFalse(isset($result['database']['db.username']));

        $result = $reader->read('TestLegacyPlugin.nested.ini');
        $this->assertEquals('foo', $result['database']['db']['password']);
        CakePlugin::unload();
    }

    /**
     * Test reading acl.ini.php from plugins.
     *
     * @return void
     */
    public function testReadPluginSpecialAclIniPhpValue(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $reader = new IniReader($this->path);
        $result = $reader->read('TestLegacyPlugin.acl.ini.php');

        $this->assertTrue(isset($result['admin']));
        $this->assertTrue(isset($result['paul']['groups']));
        $this->assertEquals('ads', $result['admin']['deny']);
        CakePlugin::unload();
    }
}
