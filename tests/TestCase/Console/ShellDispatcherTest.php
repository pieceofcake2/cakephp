<?php

/**
 * ShellDispatcherTest file
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
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console;

use Cake\Console\ConsoleErrorHandler;
use Cake\Console\Shell;
use Cake\Console\ShellDispatcher;
use Cake\Core\App;
use Cake\Core\CakeObject;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Error\MissingShellException;
use Cake\Error\MissingShellMethodException;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Debugger;
use Cake\Utility\Folder;
use TestApp\Console\Command\NamespacedShell;
use TestApp\Console\Command\SampleShell;
use TestPlugin\Console\Command\ExampleShell;
use TestPlugin\Console\Command\TestPluginShell;

/**
 * TestShellDispatcher class
 *
 * @package       Cake.Test.Case.Console
 */
class TestShellDispatcher extends ShellDispatcher
{
    /**
     * params property
     *
     * @var array
     */
    public $params = [];

    /**
     * stopped property
     *
     * @var string|null
     */
    public $stopped = null;

    /**
     * TestShell
     *
     * @var object
     */
    public $TestShell;

    /**
     * _initEnvironment method
     *
     * @return void
     */
    protected function _initEnvironment(): void
    {
    }

    /**
     * clear method
     *
     * @return void
     */
    public function clear(): void
    {
    }

    /**
     * _stop method
     *
     * @return void
     */
    protected function _stop($status = 0): mixed
    {
        $this->stopped = 'Stopped with status: ' . $status;
    }

    /**
     * @return array
     */
    public function getDefaults(): mixed
    {
        return $this->_getDefaults();
    }

    /**
     * getShell
     *
     * @param string $shell
     * @return mixed
     */
    public function getShell($shell): mixed
    {
        return $this->_getShell($shell);
    }

    /**
     * _getShell
     *
     * @param string $plugin
     * @return : object
     */
    protected function _getShell($shell): mixed
    {
        return $this->TestShell ?? parent::_getShell($shell);
    }
}

/**
 * ShellDispatcher that records the bootstrap steps instead of running them
 *
 * @package       Cake.Test.Case.Console
 */
class TestBootstrapShellDispatcher extends ShellDispatcher
{
    /**
     * The result of _bootstrap()
     *
     * @var bool
     */
    public $bootstrapResult = true;

    /**
     * The bootstrap steps that were called
     *
     * @var array<string>
     */
    public $calls = [];

    /**
     * _initConstants method
     *
     * @return void
     */
    protected function _initConstants(): void
    {
        $this->calls[] = '_initConstants';
    }

    /**
     * _bootstrap method
     *
     * @return bool
     */
    protected function _bootstrap(): mixed
    {
        $this->calls[] = '_bootstrap';

        return $this->bootstrapResult;
    }

    /**
     * Calls _initEnvironment()
     *
     * @return void
     */
    public function initEnvironment(): void
    {
        $this->_initEnvironment();
    }
}

/**
 * ShellDispatcher that exposes the real bootstrap steps
 *
 * @package       Cake.Test.Case.Console
 */
class TestEnvironmentShellDispatcher extends ShellDispatcher
{
    /**
     * Calls _initConstants()
     *
     * @return void
     */
    public function initConstants(): void
    {
        $this->_initConstants();
    }

    /**
     * Calls _bootstrap()
     *
     * @return bool
     */
    public function bootstrap(): mixed
    {
        return $this->_bootstrap();
    }
}

/**
 * ShellDispatcherTest
 *
 * @package       Cake.Test.Case.Console
 */
class ShellDispatcherTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Configure::write('App.namespace', 'TestApp');

        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS,
            ],
            'Console/Command' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Console' . DS . 'Command' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * testParseParams method
     *
     * @return void
     */
    public function testParseParamsAppWorkingAbsolute(): void
    {
        $Dispatcher = new TestShellDispatcher();
        $params = [
            '/cake/1.2.x.x/cake/console/cake.php',
            'bake',
            '-app',
            'new',
            '-working',
            '/var/www/htdocs',
        ];
        $expected = [
            'app' => 'new',
            'webroot' => 'webroot',
            'working' => str_replace('/', DS, '/var/www/htdocs/new'),
            'root' => str_replace('/', DS, '/var/www/htdocs'),
        ];
        $Dispatcher->parseParams($params);
        $this->assertEquals($expected, $Dispatcher->params);
    }

    /**
     * testParseParams method
     *
     * @return void
     */
    public function testParseParamsNone(): void
    {
        $dispatcher = new TestShellDispatcher();
        $defaults = $dispatcher->getDefaults();

        $params = ['cake.php'];
        $expected = [
            'app' => $defaults['app'],
            'webroot' => 'webroot',
            'working' => str_replace('\\', DS, $defaults['root'] . DS . $defaults['app']),
            'root' => str_replace('\\', DS, $defaults['root']),
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);
    }

    /**
     * testParseParams method
     *
     * @return void
     */
    public function testParseParamsApp(): void
    {
        $dispatcher = new TestShellDispatcher();
        $defaults = $dispatcher->getDefaults();

        $params = [
            'cake.php',
            '-app',
            'new',
        ];
        $expected = [
            'app' => 'new',
            'webroot' => 'webroot',
            'working' => str_replace('\\', DS, $defaults['root'] . DS . 'new'),
            'root' => str_replace('\\', DS, $defaults['root']),
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);
    }

    /**
     * testParseParams method
     *
     * @return void
     */
    public function testParseParamsAppWorkingRelative(): void
    {
        $dispatcher = new TestShellDispatcher();
        $defaults = $dispatcher->getDefaults();

        $params = [
            './cake.php',
            'bake',
            '-app',
            'new',
            '-working',
            '/cake/1.2.x.x/cake/console',
        ];
        $expected = [
            'app' => 'new',
            'webroot' => 'webroot',
            'working' => str_replace('\\', DS, $defaults['root'] . DS . 'new'),
            'root' => str_replace('\\', DS, $defaults['root']),
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);
    }

    /**
     * testParseParams method
     *
     * @return void
     */
    public function testParseParams(): void
    {
        $dispatcher = new TestShellDispatcher();
        $defaults = $dispatcher->getDefaults();

        $params = [
            './console/cake.php',
            'bake',
            '-app',
            'new',
            '-working',
            '/cake/1.2.x.x/cake',
        ];
        $expected = [
            'app' => 'new',
            'webroot' => 'webroot',
            'working' => str_replace('\\', DS, $defaults['root'] . DS . 'new'),
            'root' => str_replace('\\', DS, $defaults['root']),
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            './console/cake.php',
            'bake',
            '-app',
            'new',
            '-dry',
            '-working',
            '/cake/1.2.x.x/cake',
        ];
        $expected = [
            'app' => 'new',
            'working' => str_replace('\\', DS, $defaults['root'] . DS . 'new'),
            'root' => str_replace('\\', DS, $defaults['root']),
            'webroot' => 'webroot',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            './console/cake.php',
            '-working',
            '/cake/1.2.x.x/cake',
            'schema',
            'run',
            'create',
            '-dry',
            '-f',
            '-name',
            'DbAcl',
        ];
        $expected = [
            'app' => $defaults['app'],
            'webroot' => 'webroot',
            'working' => str_replace('\\', DS, $defaults['root'] . DS . $defaults['app']),
            'root' => str_replace('\\', DS, $defaults['root']),
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $expected = [
            './console/cake.php', 'schema', 'run', 'create', '-dry', '-f', '-name', 'DbAcl',
        ];
        $this->assertEquals($expected, $dispatcher->args);

        $params = [
            '/cake/1.2.x.x/cake/console/cake.php',
            '-working',
            '/cake/1.2.x.x/app',
            'schema',
            'run',
            'create',
            '-dry',
            '-name',
            'DbAcl',
        ];
        $expected = [
            'app' => 'app',
            'webroot' => 'webroot',
            'working' => str_replace('/', DS, '/cake/1.2.x.x/app'),
            'root' => str_replace('/', DS, '/cake/1.2.x.x'),
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            'cake.php',
            '-working',
            'C:/wamp/www/cake/app',
            'bake',
            '-app',
            'C:/wamp/www/apps/cake/app',
        ];
        $expected = [
            'app' => 'app',
            'webroot' => 'webroot',
            'working' => 'C:\wamp\www\apps\cake\app',
            'root' => 'C:\wamp\www\apps\cake',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            'cake.php',
            '-working',
            'C:\wamp\www\cake\app',
            'bake',
            '-app',
            'C:\wamp\www\apps\cake\app',
        ];
        $expected = [
            'app' => 'app',
            'webroot' => 'webroot',
            'working' => 'C:\wamp\www\apps\cake\app',
            'root' => 'C:\wamp\www\apps\cake',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            'cake.php',
            '-working',
            'C:\wamp\www\apps',
            'bake',
            '-app',
            'cake\app',
            '-url',
            'http://example.com/some/url/with/a/path',
        ];
        $expected = [
            'app' => 'app',
            'webroot' => 'webroot',
            'working' => 'C:\wamp\www\apps\cake\app',
            'root' => 'C:\wamp\www\apps\cake',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            '/home/amelo/dev/cake-common/cake/console/cake.php',
            '-root',
            '/home/amelo/dev/lsbu-vacancy',
            '-working',
            '/home/amelo/dev/lsbu-vacancy',
            '-app',
            'app',
        ];
        $expected = [
            'app' => 'app',
            'webroot' => 'webroot',
            'working' => '/home/amelo/dev/lsbu-vacancy/app',
            'root' => '/home/amelo/dev/lsbu-vacancy',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        $params = [
            '/cake/1.2.x.x/cake/console/cake.php',
            'bake',
            '-app',
            'new',
            '-app',
            'old',
            '-working',
            '/var/www/htdocs',
        ];
        $expected = [
            'app' => 'old',
            'webroot' => 'webroot',
            'working' => str_replace('/', DS, '/var/www/htdocs/old'),
            'root' => str_replace('/', DS, '/var/www/htdocs'),
        ];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);

        if (DS === '\\') {
            $params = [
                'cake.php',
                '-working',
                'D:\www',
                'bake',
                'my_app',
            ];
            $expected = [
                'working' => 'D:\\\\www',
                'app' => 'www',
                'root' => 'D:\\',
                'webroot' => 'webroot',
            ];

            $dispatcher->params = $dispatcher->args = [];
            $dispatcher->parseParams($params);
            $this->assertEquals($expected, $dispatcher->params);
        }
    }

    /**
     * Verify loading of (plugin-) shells
     *
     * @return void
     */
    public function testGetShell(): void
    {
        $this->skipIf(class_exists(SampleShell::class, false), 'SampleShell Class already loaded.');
        $this->skipIf(class_exists(ExampleShell::class, false), 'ExampleShell Class already loaded.');

        $dispatcher = new TestShellDispatcher();

        $result = $dispatcher->getShell('sample');
        $this->assertInstanceOf(SampleShell::class, $result);

        $dispatcher = new TestShellDispatcher();
        $result = $dispatcher->getShell('test_plugin.example');
        $this->assertInstanceOf(ExampleShell::class, $result);
        $this->assertEquals('TestPlugin', $result->plugin);
        $this->assertEquals('Example', $result->name);

        $dispatcher = new TestShellDispatcher();
        $result = $dispatcher->getShell('TestPlugin.example');
        $this->assertInstanceOf(ExampleShell::class, $result);

        $dispatcher = new TestShellDispatcher();
        $result = $dispatcher->getShell('test_plugin');
        $this->assertInstanceOf(TestPluginShell::class, $result);

        $dispatcher = new TestShellDispatcher();
        $result = $dispatcher->getShell('TestPlugin');
        $this->assertInstanceOf(TestPluginShell::class, $result);
    }

    /**
     * Verify correct dispatch of Shell subclasses with a main method
     *
     * @return void
     */
    public function testDispatchShellWithMain(): void
    {
        $Dispatcher = new TestShellDispatcher();
        $Shell = $this->getMock(Shell::class);

        $Shell->expects($this->once())->method('initialize');
        $Shell->expects($this->once())->method('runCommand')
            ->with(null, [])
            ->will($this->returnValue(true));

        $Dispatcher->TestShell = $Shell;

        $Dispatcher->args = ['mock_with_main'];
        $result = $Dispatcher->dispatch();
        $this->assertTrue($result);
        $this->assertEquals([], $Dispatcher->args);
    }

    /**
     * Verify correct dispatch of Shell subclasses without a main method
     *
     * @return void
     */
    public function testDispatchShellWithoutMain(): void
    {
        $dispatcher = new TestShellDispatcher();
        $shell = $this->getMock(Shell::class);

        $shell->expects($this->once())->method('initialize');
        $shell->expects($this->once())->method('runCommand')
            ->with('initdb', ['initdb'])
            ->will($this->returnValue(true));

        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_without_main', 'initdb'];
        $result = $dispatcher->dispatch();
        $this->assertTrue($result);
    }

    /**
     * Verify correct dispatch of custom classes with a main method
     *
     * @return void
     */
    public function testDispatchNotAShellWithMain(): void
    {
        $dispatcher = new TestShellDispatcher();
        $methods = get_class_methods(CakeObject::class);
        array_push($methods, 'main', 'initdb', 'initialize', 'loadTasks', 'startup', '_secret');

        $shell = $this->getMock(CakeObject::class, $methods);
        $shell
            ->expects($this->never())
            ->method('initialize');
        $shell
            ->expects($this->once())
            ->method('startup');
        $shell
            ->expects($this->once())
            ->method('main')
            ->will($this->returnValue(true));
        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_with_main_not_a'];
        $result = $dispatcher->dispatch();
        $this->assertTrue($result);
        $this->assertEquals([], $dispatcher->args);

        $shell = $this
            ->getMock(CakeObject::class, $methods);
        $shell
            ->expects($this->once())
            ->method('initdb')
            ->will($this->returnValue(true));
        $shell
            ->expects($this->once())
            ->method('startup');
        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_with_main_not_a', 'initdb'];
        $result = $dispatcher->dispatch();
        $this->assertTrue($result);
    }

    /**
     * Verify correct dispatch of custom classes without a main method
     *
     * @return void
     */
    public function testDispatchNotAShellWithoutMain(): void
    {
        $dispatcher = new TestShellDispatcher();
        $methods = get_class_methods(CakeObject::class);
        array_push($methods, 'main', 'initdb', 'initialize', 'loadTasks', 'startup', '_secret');
        $shell = $this
            ->getMock(CakeObject::class, $methods);
        $shell
            ->expects($this->never())
            ->method('initialize');
        $shell
            ->expects($this->once())
            ->method('startup');
        $shell
            ->expects($this->once())
            ->method('main')
            ->will($this->returnValue(true));
        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_without_main_not_a'];
        $result = $dispatcher->dispatch();
        $this->assertTrue($result);
        $this->assertEquals([], $dispatcher->args);

        $shell = $this->getMock(CakeObject::class, $methods);
        $shell->expects($this->once())->method('initdb')->will($this->returnValue(true));
        $shell->expects($this->once())->method('startup');
        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_without_main_not_a', 'initdb'];
        $result = $dispatcher->dispatch();
        $this->assertTrue($result);
    }

    /**
     * Verify shifting of arguments
     *
     * @return void
     */
    public function testShiftArgs(): void
    {
        $Dispatcher = new TestShellDispatcher();

        $Dispatcher->args = ['a', 'b', 'c'];
        $this->assertEquals('a', $Dispatcher->shiftArgs());
        $this->assertSame($Dispatcher->args, ['b', 'c']);

        $Dispatcher->args = ['a' => 'b', 'c', 'd'];
        $this->assertEquals('b', $Dispatcher->shiftArgs());
        $this->assertSame($Dispatcher->args, ['c', 'd']);

        $Dispatcher->args = ['a', 'b' => 'c', 'd'];
        $this->assertEquals('a', $Dispatcher->shiftArgs());
        $this->assertSame($Dispatcher->args, ['b' => 'c', 'd']);

        $Dispatcher->args = [0 => 'a', 2 => 'b', 30 => 'c'];
        $this->assertEquals('a', $Dispatcher->shiftArgs());
        $this->assertSame($Dispatcher->args, [0 => 'b', 1 => 'c']);

        $Dispatcher->args = [];
        $this->assertNull($Dispatcher->shiftArgs());
        $this->assertSame([], $Dispatcher->args);
    }

    /**
     * Test that a missing application bootstrap file is reported as a plain error message
     * instead of an uncaught exception with a stack trace.
     *
     * @return void
     */
    public function testMissingBootstrapFilePrintsError(): void
    {
        $working = TMP . 'shell_dispatcher_' . uniqid() . DS . 'app';
        mkdir($working . DS . 'Config', 0777, true);
        copy(CORE_ROOT . DS . 'config' . DS . 'core.php', $working . DS . 'Config' . DS . 'core.php');
        try {
            $process = proc_open(
                [PHP_BINARY, '-d', 'display_errors=stderr', CORE_ROOT . DS . 'bin' . DS . 'cake', '-working', $working],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
                $working,
            );
            stream_get_contents($pipes[1]);
            $error = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            $code = proc_close($process);
        } finally {
            $Folder = new Folder(dirname($working));
            $Folder->delete();
        }

        $this->assertSame(1, $code);
        $this->assertStringContainsString("Error: Can't find application bootstrap file.", $error);
        $this->assertStringNotContainsString('Stack trace', $error);
        $this->assertStringNotContainsString('#0 ', $error);
    }

    /**
     * Test that the console runs with the default core.php when the application has none
     *
     * @return void
     */
    public function testRunWithoutApplicationCoreFile(): void
    {
        $this->skipIf(!function_exists('exec'), 'exec() is not available.');

        $app = TMP . 'tests' . DS . 'shell_dispatcher_no_core';
        $Folder = new Folder($app, true);
        try {
            $this->assertFileDoesNotExist($app . DS . 'Config' . DS . 'core.php');

            $command = implode(' ', [
                escapeshellarg(PHP_BINARY),
                escapeshellarg(CORE_ROOT . DS . 'bin' . DS . 'cake'),
                '-app',
                escapeshellarg($app),
                'command_list',
                '2>&1',
            ]);
            exec($command, $output, $status);
            $output = implode("\n", $output);

            $this->assertSame(0, $status, $output);
            $this->assertStringNotContainsString("Can't find application core file", $output);
            $this->assertStringContainsString('Available Shells:', $output);
        } finally {
            $Folder->delete();
        }
    }

    /**
     * Test that shells are found in App.namespace.
     *
     * @return void
     */
    public function testGetShellNamespaced(): void
    {
        $Dispatcher = new TestShellDispatcher();
        $result = $Dispatcher->getShell('namespaced');
        $this->assertInstanceOf(NamespacedShell::class, $result);
        $this->assertSame('Namespaced', $result->name);
    }

    /**
     * Test the long forms of the path options, and the webroot option.
     *
     * @return void
     */
    public function testParseParamsLongOptionsAndWebroot(): void
    {
        $dispatcher = new TestShellDispatcher();
        $params = [
            'cake.php',
            '--root',
            '/var/www',
            '--app',
            'myapp',
            '--webroot',
            'public',
            'sample',
        ];
        $expected = [
            'app' => 'myapp',
            'root' => str_replace('/', DS, '/var/www'),
            'working' => str_replace('/', DS, '/var/www/myapp'),
            'webroot' => 'public',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);
        $this->assertSame(['cake.php', 'sample'], $dispatcher->args);
    }

    /**
     * Test that a relative working directory is resolved from the current directory.
     *
     * @return void
     */
    public function testParseParamsRelativeWorking(): void
    {
        $cwd = getcwd();
        $working = realpath(CORE_TESTS . DS . 'test_app');
        chdir($working);
        try {
            $dispatcher = new TestShellDispatcher();
            $dispatcher->params = $dispatcher->args = [];
            $dispatcher->parseParams(['cake.php', '-working', '.']);
        } finally {
            chdir($cwd);
        }
        $expected = [
            'app' => basename($working),
            'root' => dirname($working),
            'working' => $working,
            'webroot' => 'webroot',
        ];
        $this->assertEquals($expected, $dispatcher->params);
    }

    /**
     * Test that the working directory is used as the root when it is the root.
     *
     * @return void
     */
    public function testParseParamsWorkingIsRoot(): void
    {
        $dispatcher = new TestShellDispatcher();
        $defaults = $dispatcher->getDefaults();

        $params = ['cake.php', '-root', '/var/www/site', '-working', '/var/www/site'];
        $expected = [
            'app' => $defaults['app'],
            'root' => str_replace('/', DS, '/var/www/site'),
            'working' => str_replace('/', DS, '/var/www/site/' . $defaults['app']),
            'webroot' => 'webroot',
        ];
        $dispatcher->params = $dispatcher->args = [];
        $dispatcher->parseParams($params);
        $this->assertEquals($expected, $dispatcher->params);
    }

    /**
     * Test that the constructor bootstraps the environment and drops the script name.
     *
     * @return void
     */
    public function testConstructBootstrap(): void
    {
        $dispatcher = new TestBootstrapShellDispatcher(['cake.php', 'sample', 'main']);
        $this->assertSame(['_initConstants', '_bootstrap'], $dispatcher->calls);
        $this->assertSame(['sample', 'main'], $dispatcher->args);

        $dispatcher = new TestBootstrapShellDispatcher(['cake.php', 'sample'], false);
        $this->assertSame([], $dispatcher->calls);
        $this->assertSame(['cake.php', 'sample'], $dispatcher->args);
    }

    /**
     * Test that a failed bootstrap stops the environment set up.
     *
     * @return void
     */
    public function testInitEnvironmentBootstrapFails(): void
    {
        $dispatcher = new TestBootstrapShellDispatcher(['cake.php'], false);
        $dispatcher->bootstrapResult = false;

        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('Unable to load CakePHP core.');
        $dispatcher->initEnvironment();
    }

    /**
     * Test that the environment set up needs the script name and the working directory.
     *
     * @return void
     */
    public function testInitEnvironmentWithoutArguments(): void
    {
        $dispatcher = new TestBootstrapShellDispatcher(['cake.php'], false);
        $dispatcher->args = [];

        try {
            $dispatcher->initEnvironment();
            $this->fail('No exception was thrown without arguments.');
        } catch (CakeException $e) {
            $this->assertStringContainsString('This file has been loaded incorrectly', $e->getMessage());
        }

        $dispatcher->args = ['cake.php'];
        unset($dispatcher->params['working']);
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage('This file has been loaded incorrectly');
        $dispatcher->initEnvironment();
    }

    /**
     * Test the core configuration. The test bootstrap already ran it, so this only checks
     * that it can run again and keeps the console settings.
     *
     * @return void
     */
    public function testInitConstants(): void
    {
        $htmlErrors = ini_get('html_errors');
        $implicitFlush = ini_get('implicit_flush');
        $maxExecutionTime = ini_get('max_execution_time');

        $dispatcher = new TestEnvironmentShellDispatcher(['cake.php'], false);
        try {
            $dispatcher->initConstants();

            $this->assertTrue(defined('CAKEPHP_SHELL'));
            $this->assertEmpty(ini_get('html_errors'));
            $this->assertSame('1', ini_get('implicit_flush'));
            $this->assertSame('0', ini_get('max_execution_time'));
        } finally {
            ini_set('html_errors', (string)$htmlErrors);
            ini_set('implicit_flush', (string)$implicitFlush);
            ini_set('max_execution_time', (string)$maxExecutionTime);
        }
    }

    /**
     * Test the bootstrap. The test bootstrap already defined the constants and loaded the
     * core, so this checks that it sets the console error handlers again.
     *
     * @return void
     */
    public function testBootstrap(): void
    {
        $format = Debugger::outputAs();
        Configure::delete('Error.consoleHandler');
        Configure::delete('Exception.consoleHandler');

        $dispatcher = new TestEnvironmentShellDispatcher(['cake.php'], false);
        try {
            $result = $dispatcher->bootstrap();
        } finally {
            restore_error_handler();
            restore_exception_handler();
            Debugger::outputAs($format);
        }
        $this->assertTrue($result);
        $this->assertInstanceOf(ConsoleErrorHandler::class, Configure::read('Error.consoleHandler.0'));
        $this->assertTrue(defined('FULL_BASE_URL'));
    }

    /**
     * Test that the default console error handlers are set when none are configured.
     *
     * @return void
     */
    public function testSetErrorHandlersDefault(): void
    {
        $format = Debugger::outputAs();
        Configure::delete('Error.consoleHandler');
        Configure::delete('Exception.consoleHandler');

        $dispatcher = new TestShellDispatcher();
        try {
            $dispatcher->setErrorHandlers();
            $outputFormat = Debugger::outputAs();
        } finally {
            $errorHandler = set_error_handler(null);
            restore_error_handler();
            restore_error_handler();
            $exceptionHandler = set_exception_handler(null);
            restore_exception_handler();
            restore_exception_handler();
            Debugger::outputAs($format);
        }

        $error = Configure::read('Error.consoleHandler');
        $this->assertInstanceOf(ConsoleErrorHandler::class, $error[0]);
        $this->assertSame('handleError', $error[1]);
        $exception = Configure::read('Exception.consoleHandler');
        $this->assertSame($error[0], $exception[0]);
        $this->assertSame('handleException', $exception[1]);

        $this->assertSame($error, $errorHandler);
        $this->assertSame($exception, $exceptionHandler);
        $this->assertSame('txt', $outputFormat);
    }

    /**
     * Test that the configured console error handlers are used.
     *
     * @return void
     */
    public function testSetErrorHandlersConfigured(): void
    {
        $format = Debugger::outputAs();
        $handler = new ConsoleErrorHandler();
        Configure::write('Error.consoleHandler', [$handler, 'handleError']);
        Configure::write('Exception.consoleHandler', [$handler, 'handleException']);

        $dispatcher = new TestShellDispatcher();
        try {
            $dispatcher->setErrorHandlers();
        } finally {
            $errorHandler = set_error_handler(null);
            restore_error_handler();
            restore_error_handler();
            $exceptionHandler = set_exception_handler(null);
            restore_exception_handler();
            restore_exception_handler();
            Debugger::outputAs($format);
        }

        $this->assertSame([$handler, 'handleError'], $errorHandler);
        $this->assertSame([$handler, 'handleException'], $exceptionHandler);
    }

    /**
     * Test that dispatching without a shell shows the command list and fails.
     *
     * @return void
     */
    public function testDispatchWithoutShellShowsHelp(): void
    {
        $dispatcher = new TestShellDispatcher();
        $shell = $this->getMock(Shell::class);
        $shell->expects($this->once())->method('initialize');
        $shell->expects($this->once())->method('runCommand')
            ->with('', [])
            ->will($this->returnValue(true));
        $dispatcher->TestShell = $shell;

        $dispatcher->args = [];
        $this->assertFalse($dispatcher->dispatch());
    }

    /**
     * Test that the help commands show the command list.
     *
     * @return void
     */
    public function testDispatchHelp(): void
    {
        foreach (['help', '--help', '-h'] as $command) {
            $dispatcher = new TestShellDispatcher();
            $shell = $this->getMock(Shell::class);
            $shell->expects($this->once())->method('initialize');
            $shell->expects($this->once())->method('runCommand')
                ->with('', [])
                ->will($this->returnValue(false));
            $dispatcher->TestShell = $shell;

            $dispatcher->args = [$command];
            $this->assertTrue($dispatcher->dispatch(), $command);
        }
    }

    /**
     * Test dispatching a plugin shell that is loaded by name.
     *
     * @return void
     */
    public function testDispatchLoadedShell(): void
    {
        CakePlugin::load('TestDispatchPlugin');

        $dispatcher = new TestShellDispatcher();
        $dispatcher->args = ['TestDispatchPlugin.dispatched', 'hello', 'world'];
        $this->assertSame('hello: world', $dispatcher->dispatch());

        $dispatcher = new TestShellDispatcher();
        $dispatcher->args = ['test_dispatch_plugin.dispatched', 'one', 'two'];
        $this->assertSame('main: one two', $dispatcher->dispatch());
    }

    /**
     * Test that a protected method of a class that is not a shell cannot be dispatched.
     *
     * @return void
     */
    public function testDispatchNotAShellProtectedMethod(): void
    {
        $dispatcher = new TestShellDispatcher();
        $methods = get_class_methods(CakeObject::class);
        array_push($methods, 'main', 'startup', '_secret');
        $shell = $this->getMock(CakeObject::class, $methods);
        $shell->expects($this->never())->method('startup');
        $shell->expects($this->never())->method('main');
        $shell->expects($this->never())->method('_secret');
        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_not_a', '_secret'];
        $this->expectException(MissingShellMethodException::class);
        $dispatcher->dispatch();
    }

    /**
     * Test that a class that is not a shell needs the method or main().
     *
     * @return void
     */
    public function testDispatchNotAShellMissingMethod(): void
    {
        $dispatcher = new TestShellDispatcher();
        $methods = get_class_methods(CakeObject::class);
        array_push($methods, 'startup');
        $shell = $this->getMock(CakeObject::class, $methods);
        $shell->expects($this->never())->method('startup');
        $dispatcher->TestShell = $shell;

        $dispatcher->args = ['mock_not_a', 'unknown'];
        $this->expectException(MissingShellMethodException::class);
        $dispatcher->dispatch();
    }

    /**
     * Test that a plugin without a shell of its name throws a missing shell exception.
     *
     * @return void
     */
    public function testGetShellMissing(): void
    {
        CakePlugin::load('TestTwoPlugin');
        $dispatcher = new TestShellDispatcher();

        $this->expectException(MissingShellException::class);
        $this->expectExceptionMessage('TestTwoPluginShell');
        $dispatcher->getShell('test_two_plugin');
    }

    /**
     * Test that an unknown shell name throws a missing shell exception.
     *
     * @return void
     */
    public function testGetShellUnknownName(): void
    {
        $this->markTestSkipped(
            'ShellDispatcher::_getShell() looks the name up as a plugin, and App::className() throws ' .
            'MissingPluginException ("Plugin NotThere could not be found.") before MissingShellException.',
        );

        $dispatcher = new TestShellDispatcher();

        $this->expectException(MissingShellException::class);
        $this->expectExceptionMessage('NotThereShell');
        $dispatcher->getShell('not_there');
    }
}
