<?php

use Composer\Autoload\ClassLoader;
use PHPUnit\Framework\Error;

/**
 * ConfigureTest file
 *
 * Holds several tests
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
 * @package       Cake.Test.Case.Core
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ConfigureTest
 *
 * @package       Cake.Test.Case.Core
 */
class ConfigureTest extends CakeTestCase
{
    protected $_restoreError = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Cache.disable', true);
        App::build();
        App::objects('plugin', null, true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_core_paths');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_dir_map');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_file_map');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'cake_core_object_map');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.config.php');
        }
        if (file_exists(TMP . 'cache' . DS . 'persistent' . DS . 'test.php')) {
            unlink(TMP . 'cache' . DS . 'persistent' . DS . 'test.php');
        }
        Configure::drop('test');

        if ($this->_restoreError) {
            restore_error_handler();
        }

        parent::tearDown();
    }

    /**
     * Test that bootstrapping throws an exception when the application core file is missing.
     *
     * @return void
     */
    public function testBootstrapMissingCoreFile(): void
    {
        $result = $this->_bootstrapInSeparateProcess([]);
        $this->assertSame(
            "ConfigureException: Can't find application core file. Please create " . $result['config'] . 'core.php, and make sure it is readable by PHP.',
            $result['output'],
        );
    }

    /**
     * Test that bootstrapping throws an exception when the application bootstrap file is missing.
     *
     * @return void
     */
    public function testBootstrapMissingBootstrapFile(): void
    {
        $result = $this->_bootstrapInSeparateProcess(['core.php' => '<?php return true;']);
        $this->assertSame(
            "ConfigureException: Can't find application bootstrap file. Please create " . $result['config'] . 'bootstrap.php, and make sure it is readable by PHP.',
            $result['output'],
        );
    }

    /**
     * Runs Configure::bootstrap() in a separate PHP process with CONFIG pointing at a temporary
     * directory holding the given files, and returns what the process wrote to STDOUT.
     *
     * @param array<string, string> $files File names and contents to create in the CONFIG directory.
     * @return array{config: string, output: string}
     */
    protected function _bootstrapInSeparateProcess(array $files): mixed
    {
        $dir = TMP . 'configure_bootstrap_' . uniqid() . DS;
        $config = $dir . 'config' . DS;
        mkdir($config, 0777, true);
        foreach ($files as $name => $contents) {
            file_put_contents($config . $name, $contents);
        }
        $loader = new ReflectionClass(ClassLoader::class);
        $script = '<?php' . "\n" .
            'define("DS", DIRECTORY_SEPARATOR);' . "\n" .
            'define("ROOT", ' . var_export(ROOT, true) . ');' . "\n" .
            'define("APP_DIR", ' . var_export(APP_DIR, true) . ');' . "\n" .
            'define("WWW_ROOT", ' . var_export(WWW_ROOT, true) . ');' . "\n" .
            'define("TMP", ' . var_export($dir, true) . ');' . "\n" .
            'define("CONFIG", ' . var_export($config, true) . ');' . "\n" .
            'require ' . var_export(dirname($loader->getFileName(), 2) . DS . 'autoload.php', true) . ';' . "\n" .
            'require ' . var_export(CORE_ROOT . DS . 'config' . DS . 'init.php', true) . ';' . "\n" .
            'try {' . "\n" .
            '    require CAKE . "bootstrap.php";' . "\n" .
            '} catch (Exception $e) {' . "\n" .
            '    fwrite(STDOUT, get_class($e) . ": " . $e->getMessage());' . "\n" .
            '}' . "\n";
        file_put_contents($dir . 'script.php', $script);

        try {
            $process = proc_open(
                [PHP_BINARY, '-d', 'display_errors=stderr', $dir . 'script.php'],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            $output = stream_get_contents($pipes[1]);
            stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } finally {
            $Folder = new Folder($dir);
            $Folder->delete();
        }

        return ['config' => $config, 'output' => $output];
    }

    /**
     * Test to ensure bootrapping doesn't overwrite prior configs set under 'App' key
     *
     * @return void
     */
    public function testBootstrap(): void
    {
        $expected = [
            'foo' => 'bar',
        ];
        Configure::write('App', $expected);

        Configure::bootstrap(true);
        $this->_restoreError = true;
        $result = Configure::read('App');

        $this->assertEquals($expected['foo'], $result['foo']);
        $this->assertFalse($result['base']);
    }

    /**
     * testRead method
     *
     * @return void
     */
    public function testRead(): void
    {
        $expected = 'ok';
        Configure::write('level1.level2.level3_1', $expected);
        Configure::write('level1.level2.level3_2', 'something_else');
        $result = Configure::read('level1.level2.level3_1');
        $this->assertEquals($expected, $result);

        $result = Configure::read('level1.level2.level3_2');
        $this->assertEquals('something_else', $result);

        $result = Configure::read('debug');
        $this->assertTrue($result >= 0);

        $result = Configure::read();
        $this->assertTrue(is_array($result));
        $this->assertTrue(isset($result['debug']));
        $this->assertTrue(isset($result['level1']));

        $result = Configure::read('something_I_just_made_up_now');
        $this->assertEquals(null, $result, 'Missing key should return null.');
    }

    /**
     * testWrite method
     *
     * @return void
     */
    public function testWrite(): void
    {
        $writeResult = Configure::write('SomeName.someKey', 'myvalue');
        $this->assertTrue($writeResult);
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        $writeResult = Configure::write('SomeName.someKey', null);
        $this->assertTrue($writeResult);
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals(null, $result);

        $expected = ['One' => ['Two' => ['Three' => ['Four' => ['Five' => 'cool']]]]];
        $writeResult = Configure::write('Key', $expected);
        $this->assertTrue($writeResult);

        $result = Configure::read('Key');
        $this->assertEquals($expected, $result);

        $result = Configure::read('Key.One');
        $this->assertEquals($expected['One'], $result);

        $result = Configure::read('Key.One.Two');
        $this->assertEquals($expected['One']['Two'], $result);

        $result = Configure::read('Key.One.Two.Three.Four.Five');
        $this->assertEquals('cool', $result);

        Configure::write('one.two.three.four', '4');
        $result = Configure::read('one.two.three.four');
        $this->assertEquals('4', $result);
    }

    /**
     * Test the consume method.
     *
     * @return void
     */
    public function testConsume(): void
    {
        $this->assertNull(Configure::consume('DoesNotExist'), 'Should be null on empty value');
        Configure::write('Test', ['key' => 'value', 'key2' => 'value2']);

        $result = Configure::consume('Test.key');
        $this->assertEquals('value', $result);

        $result = Configure::read('Test.key2');
        $this->assertEquals('value2', $result, 'Other values should remain.');

        $result = Configure::consume('Test');
        $expected = ['key2' => 'value2'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testConsumeEmpty
     *
     * @return void
     */
    public function testConsumeEmpty(): void
    {
        Configure::write('Test', ['key' => 'value', 'key2' => 'value2']);
        $result = Configure::consume('');
        $this->assertNull($result);
        $result = Configure::consume(null);
        $this->assertNull($result);
    }

    /**
     * test setting display_errors with debug.
     *
     * @return void
     */
    public function testDebugSettingDisplayErrors(): void
    {
        Configure::write('debug', 0);
        $result = ini_get('display_errors');
        $this->assertEquals(0, $result);

        Configure::write('debug', 2);
        $result = ini_get('display_errors');
        $this->assertEquals(1, $result);
    }

    /**
     * testDelete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        Configure::write('SomeName.someKey', 'myvalue');
        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        Configure::delete('SomeName.someKey');
        $result = Configure::read('SomeName.someKey');
        $this->assertNull($result);

        Configure::write('SomeName', ['someKey' => 'myvalue', 'otherKey' => 'otherValue']);

        $result = Configure::read('SomeName.someKey');
        $this->assertEquals('myvalue', $result);

        $result = Configure::read('SomeName.otherKey');
        $this->assertEquals('otherValue', $result);

        Configure::delete('SomeName');

        $result = Configure::read('SomeName.someKey');
        $this->assertNull($result);

        $result = Configure::read('SomeName.otherKey');
        $this->assertNull($result);
    }

    /**
     * testCheck method
     *
     * @return void
     */
    public function testCheck(): void
    {
        Configure::write('ConfigureTestCase', 'value');
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertFalse(Configure::check('NotExistingConfigureTestCase'));
    }

    /**
     * testCheckingSavedEmpty method
     *
     * @return void
     */
    public function testCheckingSavedEmpty(): void
    {
        $this->assertTrue(Configure::write('ConfigureTestCase', 0));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', '0'));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', false));
        $this->assertTrue(Configure::check('ConfigureTestCase'));

        $this->assertTrue(Configure::write('ConfigureTestCase', null));
        $this->assertFalse(Configure::check('ConfigureTestCase'));
    }

    /**
     * testCheckKeyWithSpaces method
     *
     * @return void
     */
    public function testCheckKeyWithSpaces(): void
    {
        $this->assertTrue(Configure::write('Configure Test', 'test'));
        $this->assertTrue(Configure::check('Configure Test'));
        Configure::delete('Configure Test');

        $this->assertTrue(Configure::write('Configure Test.Test Case', 'test'));
        $this->assertTrue(Configure::check('Configure Test.Test Case'));
    }

    /**
     * testCheckEmpty
     *
     * @return void
     */
    public function testCheckEmpty(): void
    {
        $this->assertFalse(Configure::check(''));
        $this->assertFalse(Configure::check(null));
    }

    /**
     * testLoad method
     *
     * @return void
     */
    public function testLoadExceptionOnNonExistantFile(): void
    {
        $this->expectException(RuntimeException::class);
        Configure::config('test', new PhpReader());
        Configure::load('non_existing_configuration_file', 'test');
    }

    /**
     * test load method for default config creation
     *
     * @return void
     */
    public function testLoadDefaultConfig(): void
    {
        try {
            Configure::load('non_existing_configuration_file');
        } catch (Exception) {
            $result = Configure::configured('default');
            $this->assertTrue($result);
        }
    }

    /**
     * test load with merging
     *
     * @return void
     */
    public function testLoadWithMerge(): void
    {
        Configure::config('test', new PhpReader(CORE_TESTS . DS . 'test_app' . DS . 'Config' . DS));

        $result = Configure::load('var_test', 'test');
        $this->assertTrue($result);

        $this->assertEquals('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', true);
        $this->assertTrue($result);

        $this->assertEquals('value2', Configure::read('Read'));
        $this->assertEquals('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertEquals('buried', Configure::read('Deep.Deeper.Deepest'));
        $this->assertEquals('Overwrite', Configure::read('TestAcl.classname'));
        $this->assertEquals('one', Configure::read('TestAcl.custom'));
    }

    /**
     * test loading with overwrite
     *
     * @return void
     */
    public function testLoadNoMerge(): void
    {
        Configure::config('test', new PhpReader(CORE_TESTS . DS . 'test_app' . DS . 'Config' . DS));

        $result = Configure::load('var_test', 'test');
        $this->assertTrue($result);

        $this->assertEquals('value', Configure::read('Read'));

        $result = Configure::load('var_test2', 'test', false);
        $this->assertTrue($result);

        $this->assertEquals('value2', Configure::read('Read'));
        $this->assertEquals('buried2', Configure::read('Deep.Second.SecondDeepest'));
        $this->assertNull(Configure::read('Deep.Deeper.Deepest'));
    }

    /**
     * testLoad method
     *
     * @return void
     */
    public function testLoadPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        Configure::config('test', new PhpReader());
        CakePlugin::load('TestPlugin');
        $result = Configure::load('TestPlugin.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/plugins/test_plugin/config/load.php';
        $config = Configure::read('plugin_load');
        $this->assertEquals($expected, $config);

        $result = Configure::load('TestPlugin.more.load', 'test');
        $this->assertTrue($result);
        $expected = '/test_app/plugins/test_plugin/config/more.load.php';
        $config = Configure::read('plugin_more_load');
        $this->assertEquals($expected, $config);
        CakePlugin::unload();
    }

    /**
     * testStore method
     *
     * @return void
     */
    public function testStoreAndRestore(): void
    {
        Configure::write('Cache.disable', false);

        Configure::write('Testing', 'yummy');
        $this->assertTrue(Configure::store('store_test', 'default'));

        Configure::delete('Testing');
        $this->assertNull(Configure::read('Testing'));

        Configure::restore('store_test', 'default');
        $this->assertEquals('yummy', Configure::read('Testing'));

        Cache::delete('store_test', 'default');
    }

    /**
     * test that store and restore only store/restore the provided data.
     *
     * @return void
     */
    public function testStoreAndRestoreWithData(): void
    {
        Configure::write('Cache.disable', false);

        Configure::write('testing', 'value');
        Configure::store('store_test', 'default', ['store_test' => 'one']);
        Configure::delete('testing');
        $this->assertNull(Configure::read('store_test'), 'Calling store with data shouldn\'t modify runtime.');

        Configure::restore('store_test', 'default');
        $this->assertEquals('one', Configure::read('store_test'));
        $this->assertNull(Configure::read('testing'), 'Values that were not stored are not restored.');

        Cache::delete('store_test', 'default');
    }

    /**
     * testVersion method
     *
     * @return void
     */
    public function testVersion(): void
    {
        $result = Configure::version();
        $this->assertTrue(version_compare($result, '1.2', '>='));
    }

    /**
     * test adding new readers.
     *
     * @return void
     */
    public function testReadersetUp(): void
    {
        $reader = new PhpReader();
        Configure::config('test', $reader);
        $configured = Configure::configured();

        $this->assertTrue(in_array('test', $configured));

        $this->assertTrue(Configure::configured('test'));
        $this->assertFalse(Configure::configured('fake_garbage'));

        $this->assertTrue(Configure::drop('test'));
        $this->assertFalse(Configure::drop('test'), 'dropping things that do not exist should return false.');
    }

    /**
     * test reader() throwing exceptions on missing interface.
     *
     * @return void
     * @throws Error
     */
    public function testReaderExceptionOnIncorrectClass(): void
    {
        $this->expectException(Error::class);
        $reader = new StdClass();

        try {
            Configure::config('test', $reader);
        } catch (TypeError) {
            throw new Error('Raised an error', 100);
        }
    }

    /**
     * Test that clear wipes all values.
     *
     * @return void
     */
    public function testClear(): void
    {
        Configure::write('test', 'value');
        $this->assertTrue(Configure::clear());
        $this->assertNull(Configure::read('debug'));
        $this->assertNull(Configure::read('test'));
    }

    /**
     * testDumpNoAdapter
     *
     * @return void
     */
    public function testDumpNoAdapter(): void
    {
        $this->expectException(ConfigureException::class);
        Configure::dump(TMP . 'test.php', 'does_not_exist');
    }

    /**
     * test dump integrated with the PhpReader.
     *
     * @return void
     */
    public function testDump(): void
    {
        Configure::config('test_reader', new PhpReader(TMP));

        $result = Configure::dump('config_test.php', 'test_reader');
        $this->assertTrue($result > 0);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('$config = ', $result);
        if (file_exists(TMP . 'config_test.php')) {
            unlink(TMP . 'config_test.php');
        }
    }

    /**
     * Test dumping only some of the data.
     *
     * @return void
     */
    public function testDumpPartial(): void
    {
        Configure::config('test_reader', new PhpReader(TMP));

        $result = Configure::dump('config_test.php', 'test_reader', ['Error']);
        $this->assertTrue($result > 0);
        $result = file_get_contents(TMP . 'config_test.php');
        $this->assertStringContainsString('<?php', $result);
        $this->assertStringContainsString('$config = ', $result);
        $this->assertStringContainsString('Error', $result);
        $this->assertStringNotContainsString('debug', $result);

        if (file_exists(TMP . 'config_test.php')) {
            unlink(TMP . 'config_test.php');
        }
    }
}
