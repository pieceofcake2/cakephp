<?php
/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingPluginException;
use Cake\Error\MissingTestLoaderException;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\CakeTestLoader;
use Cake\TestSuite\CakeTestSuiteCommand;
use Cake\TestSuite\Reporter\CakeHtmlReporter;
use Cake\TestSuite\Reporter\CakeTextReporter;
use Cake\Utility\Folder;
use ReflectionMethod;
use ReflectionProperty;
use TestApp\TestSuite\Reporter\NamespacedReporter;

/**
 * CakeTestSuiteCommandTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestSuiteCommandTest extends CakeTestCase
{
    /**
     * Temporary plugins directory
     *
     * @var string
     */
    protected string $pluginsPath;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->pluginsPath = TMP . 'tests' . DS . 'cake_test_suite_command_plugins' . DS;

        $dirs = [
            'ModernCommandPlugin' . DS . 'tests' . DS . 'TestCase',
            'LegacyCommandPlugin' . DS . 'Test' . DS . 'Case',
        ];
        foreach ($dirs as $dir) {
            if (!is_dir($this->pluginsPath . $dir)) {
                mkdir($this->pluginsPath . $dir, 0777, true);
            }
        }

        App::build(['Plugin' => [$this->pluginsPath]], App::RESET);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload('ModernCommandPlugin');
        CakePlugin::unload('LegacyCommandPlugin');
        $Folder = new Folder($this->pluginsPath);
        $Folder->delete();

        parent::tearDown();
    }

    /**
     * Returns the arguments that the command passes to PHPUnit.
     *
     * The command has no public accessor for them, so this reads the protected
     * $arguments property of PHPUnit's TextUI Command, which the command fills.
     * The tests that use it depend on that internal property of PHPUnit.
     *
     * @param CakeTestSuiteCommand $command The command.
     * @return array
     */
    protected function _arguments(CakeTestSuiteCommand $command): array
    {
        $property = new ReflectionProperty(CakeTestSuiteCommand::class, 'arguments');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }

        return $property->getValue($command);
    }

    /**
     * Resolves a test case to the file that is passed to PHPUnit.
     *
     * @param array $params The parameters of the command.
     * @return string
     */
    protected function _resolve(array $params): string
    {
        $command = new CakeTestSuiteCommand(CakeTestLoader::class, $params);
        $method = new ReflectionMethod(CakeTestSuiteCommand::class, '_resolveTestFile');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        return $method->invoke($command, $params['case'], $params);
    }

    /**
     * Test that a loader class that does not exist is rejected.
     *
     * @return void
     */
    public function testConstructorRejectsMissingLoader(): void
    {
        $this->expectException(MissingTestLoaderException::class);
        $this->expectExceptionMessage('Test loader Cake\TestSuite\NoSuchTestLoader could not be found.');

        new CakeTestSuiteCommand('Cake\TestSuite\NoSuchTestLoader', ['case' => 'Model/Model']);
    }

    /**
     * Test that the constructor passes the loader and the case to PHPUnit, and that the
     * --fixture option sets the fixture manager.
     *
     * @return void
     */
    public function testConstructorAndHandleFixtureSetArguments(): void
    {
        $params = ['case' => 'Model/Model', 'core' => true];
        $command = new CakeTestSuiteCommand(CakeTestLoader::class, $params);
        $command->handleFixture('MyFixtureManager');

        $arguments = $this->_arguments($command);
        $this->assertSame(CakeTestLoader::class, $arguments['loader']);
        $this->assertSame('Model/Model', $arguments['test']);
        $this->assertSame($params, $arguments['testFile']);
        $this->assertSame('MyFixtureManager', $arguments['fixtureManager']);
    }

    /**
     * Test that the --output option picks the core reporter, gives it the parameters of the
     * command, and uses it as the PHPUnit printer.
     *
     * @return void
     */
    public function testHandleReporterUsesCoreReporter(): void
    {
        $params = ['case' => 'Model/Model', 'core' => true, 'codeCoverage' => false];
        $command = new CakeTestSuiteCommand('', $params);

        $reporter = $command->handleReporter('text');

        $this->assertSame(CakeTextReporter::class, $reporter::class);
        $this->assertSame($params, $reporter->params);
        $this->assertSame($reporter, $this->_arguments($command)['printer']);

        $this->assertSame(CakeHtmlReporter::class, $command->handleReporter('html')::class);
    }

    /**
     * Test that a reporter of the application is used when there is one for the output.
     *
     * @return void
     */
    public function testHandleReporterUsesAppReporter(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $params = ['case' => 'Model/Model', 'app' => true];
        $command = new CakeTestSuiteCommand('', $params);

        $reporter = $command->handleReporter('namespaced');

        $this->assertSame(NamespacedReporter::class, $reporter::class);
        $this->assertSame($params, $reporter->params);
    }

    /**
     * Test that a core case resolves to the test file in tests/TestCase of the core, with the
     * Test.php suffix added only when it is missing.
     *
     * @return void
     */
    public function testResolveTestFileCore(): void
    {
        $expected = CORE_TESTS . DS . 'TestCase' . DS . 'Model' . DS . 'ModelTest.php';

        $this->assertSame($expected, $this->_resolve(['case' => 'Model' . DS . 'Model', 'core' => true]));
        $this->assertSame($expected, $this->_resolve(['case' => 'Model' . DS . 'ModelTest.php', 'core' => true]));
    }

    /**
     * Test that a plugin case resolves to tests/TestCase or to Test/Case of the plugin, and that
     * the plugin is loaded for it.
     *
     * @return void
     */
    public function testResolveTestFilePlugin(): void
    {
        $this->assertSame(
            $this->pluginsPath . 'ModernCommandPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'PostTest.php',
            $this->_resolve(['case' => 'Model' . DS . 'Post', 'plugin' => 'ModernCommandPlugin']),
        );
        $this->assertTrue(CakePlugin::loaded('ModernCommandPlugin'));
        $this->assertSame(
            $this->pluginsPath . 'LegacyCommandPlugin' . DS . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'PostTest.php',
            $this->_resolve(['case' => 'Model' . DS . 'Post', 'plugin' => 'LegacyCommandPlugin']),
        );

        // The plugins are loaded now
        $this->assertSame(
            $this->pluginsPath . 'ModernCommandPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'PostTest.php',
            $this->_resolve(['case' => 'Model' . DS . 'Post', 'plugin' => 'ModernCommandPlugin']),
        );
        $this->assertSame(
            $this->pluginsPath . 'LegacyCommandPlugin' . DS . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'PostTest.php',
            $this->_resolve(['case' => 'Model' . DS . 'Post', 'plugin' => 'LegacyCommandPlugin']),
        );
    }

    /**
     * Test that a case of a plugin that does not exist is rejected with an
     * exception that names the plugin, and that the plugin is not loaded.
     *
     * @return void
     */
    public function testResolveTestFileMissingPlugin(): void
    {
        $this->markTestIncomplete('Known bug: _basePath() swallows MissingPluginException, so the case resolves to /Model/PostTest.php');

        try {
            $file = $this->_resolve(['case' => 'Model' . DS . 'Post', 'plugin' => 'NoSuchCommandPlugin']);
            $this->fail('The case of a missing plugin resolved to ' . $file);
        } catch (MissingPluginException $e) {
            $this->assertStringContainsString('NoSuchCommandPlugin', $e->getMessage());
        }
        $this->assertFalse(CakePlugin::loaded('NoSuchCommandPlugin'));
    }
}
