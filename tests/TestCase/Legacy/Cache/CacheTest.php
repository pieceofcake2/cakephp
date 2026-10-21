<?php

/**
 * CacheTest file
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
 * @package       Cake.Test.Case.Cache
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Cache;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\CacheException;
use Cake\TestSuite\CakeTestCase;

/**
 * CacheTest class
 *
 * @package       Cake.Test.Case.Cache
 */
class CacheTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_cacheDisable = Configure::read('Cache.disable');
        Configure::write('Cache.disable', false);

        $this->_defaultCacheConfig = Cache::config('default');
        Cache::config('default', ['engine' => 'File', 'path' => TMP . 'tests']);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Cache::drop('latest');
        Cache::drop('page');
        Cache::drop('archive');
        Configure::write('Cache.disable', $this->_cacheDisable);
        Cache::config('default', $this->_defaultCacheConfig['settings']);

        parent::tearDown();
    }

    /**
     * testConfig method
     *
     * @return void
     */
    public function testConfig(): void
    {
        $settings = ['engine' => 'File', 'path' => TMP . 'tests', 'prefix' => 'cake_test_'];
        $results = Cache::config('new', $settings);
        $this->assertEquals(Cache::config('new'), $results);
        $this->assertTrue(isset($results['engine']));
        $this->assertTrue(isset($results['settings']));
    }

    /**
     * testConfigInvalidEngine method
     *
     * @return void
     */
    public function testConfigInvalidEngine(): void
    {
        $this->expectException(CacheException::class);
        $settings = ['engine' => 'Imaginary'];
        Cache::config('imaginary', $settings);
    }

    /**
     * test configuring CacheEngines in App/libs
     *
     * @return void
     */
    public function testConfigWithLibAndPluginEngines(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $settings = ['engine' => 'TestAppCache', 'path' => TMP, 'prefix' => 'cake_test_'];
        $result = Cache::config('libEngine', $settings);
        $this->assertEquals(Cache::config('libEngine'), $result);

        $settings = ['engine' => 'TestLegacyPlugin.TestPluginCache', 'path' => TMP, 'prefix' => 'cake_test_'];
        $result = Cache::config('pluginLibEngine', $settings);
        $this->assertEquals(Cache::config('pluginLibEngine'), $result);

        Cache::drop('libEngine');
        Cache::drop('pluginLibEngine');

        App::build();
        CakePlugin::unload();
    }

    /**
     * test that trying to configure classes that don't extend CacheEngine fail.
     *
     * @return void
     */
    public function testAttemptingToConfigureANonCacheEngineClass(): void
    {
        $this->expectException(CacheException::class);
        $this->getMock('StdClass', [], [], 'RubbishEngine');
        Cache::config('Garbage', [
            'engine' => 'Rubbish',
        ]);
    }

    /**
     * test that drop removes cache configs, and that further attempts to use that config
     * do not work.
     *
     * @return void
     */
    public function testDrop(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);

        $result = Cache::drop('some_config_that_does_not_exist');
        $this->assertFalse($result);

        Cache::config('tests', ['engine' => 'File', 'path' => TMP . 'tests']);
        $_testsConfig = Cache::config('tests');
        $result = Cache::drop('tests');
        $this->assertTrue($result);

        Cache::config('unconfigTest', [
            'engine' => 'TestAppCache',
        ]);
        $this->assertTrue(Cache::isInitialized('unconfigTest'));

        $this->assertTrue(Cache::drop('unconfigTest'));
        $this->assertFalse(Cache::isInitialized('TestAppCache'));

        Cache::config('tests', $_testsConfig);
        App::build();
    }

    /**
     * Test that failed writes cause errors to be triggered.
     *
     * @return void
     */
    public function testWriteTriggerError(): void
    {
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);

        $errorTriggered = false;
        $errorMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$errorTriggered, &$errorMessage) {
            $errorTriggered = true;
            $errorMessage = $errstr;

            return true;
        });

        try {
            Cache::config('test_trigger', ['engine' => 'TestAppCache', 'prefix' => '']);
            Cache::write('fail', 'value', 'test_trigger');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($errorTriggered, 'Expected error was not triggered');
        $this->assertSame('test_trigger cache was unable to write \'fail\' to TestAppCache cache', $errorMessage);

        Cache::drop('test_trigger');
        App::build();
    }
}
