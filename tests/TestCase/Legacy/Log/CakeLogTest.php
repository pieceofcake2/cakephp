<?php
/**
 * CakeLogTest file
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
 * @package       Cake.Test.Case.Log
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Log;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\CakeLogException;
use Cake\Log\CakeLog;
use Cake\TestSuite\CakeTestCase;

/**
 * CakeLogTest class
 *
 * @package       Cake.Test.Case.Log
 */
class CakeLogTest extends CakeTestCase
{
    /**
     * Start test callback, clears all streams enabled.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $streams = CakeLog::configured();
        foreach ($streams as $stream) {
            CakeLog::drop($stream);
        }
    }

    /**
     * test importing loggers from app/libs and plugins.
     *
     * @return void
     */
    public function testImportingLoggers(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $result = CakeLog::config('libtest', [
            'engine' => 'TestAppLog',
        ]);
        $this->assertTrue($result);
        $this->assertEquals(CakeLog::configured(), ['libtest']);

        $result = CakeLog::config('plugintest', [
            'engine' => 'TestLegacyPlugin.TestPluginLog',
        ]);
        $this->assertTrue($result);
        $this->assertEquals(CakeLog::configured(), ['libtest', 'plugintest']);

        CakeLog::write(LOG_INFO, 'TestPluginLog is not a BaseLog descendant');

        App::build();
        CakePlugin::unload();
    }

    /**
     * test config() with valid key name
     *
     * @return void
     */
    public function testValidKeyName(): void
    {
        CakeLog::config('valid', ['engine' => 'File']);
        $stream = CakeLog::stream('valid');
        $this->assertInstanceOf('FileLog', $stream);
        CakeLog::drop('valid');
    }

    /**
     * test config() with valid key name including the deprecated Log suffix
     *
     * @return void
     */
    public function testValidKeyNameLogSuffix(): void
    {
        CakeLog::config('valid', ['engine' => 'FileLog']);
        $stream = CakeLog::stream('valid');
        $this->assertInstanceOf('FileLog', $stream);
        CakeLog::drop('valid');
    }

    /**
     * test that loggers have to implement the correct interface.
     *
     * @return void
     */
    public function testNotImplementingInterface(): void
    {
        $this->expectException(CakeLogException::class);
        CakeLog::config('fail', ['engine' => 'stdClass']);
    }
}
