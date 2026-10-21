<?php

/**
 * TestSuiteShell test case
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\TestShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\CakeTestSuiteDispatcher;
use Cake\Utility\Folder;
use Exception;
use ReflectionProperty;

/**
 * TestTestShell
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestTestShell extends TestShell
{
    public function mapFileToCase($file, $category, $throwOnMissingFile = true): mixed
    {
        return $this->_mapFileToCase($file, $category, $throwOnMissingFile);
    }

    public function mapFileToCategory($file): mixed
    {
        return $this->_mapFileToCategory($file);
    }
}

/**
 * TestShellTest
 *
 * @package Cake.Test.Case.Console.Command
 * @property TestTestShell $Shell
 */
class TestShellTest extends CakeTestCase
{
    /**
     * setUp test case
     *
     * @return void
     */
    public function setUp(): void
    {
        // This test can not be run with PHPUnit 9+.
        // Because TestShell and CakeTestLoader depend on PHPUnit_Runner_StandardTestSuiteLoader.
        // If run the test with the phpunit command, TestSuite Loader is also PHPUnit's to be used.
        // Therefore, TestShell and CakeTestLoader are unnecessary.
        parent::setUp();
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            TestTestShell::class,
            ['in', 'out', 'hr', 'help', 'error', 'err', '_stop', 'initialize', '_run', 'clear'],
            [$out, $out, $in],
        );
        $this->Shell->OptionParser = $this->getMock(ConsoleOptionParser::class, [], [null, false]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Dispatch, $this->Shell);
        $Folder = new Folder($this->_pluginsPath());
        $Folder->delete();

        parent::tearDown();
    }

    /**
     * testMapCoreFileToCategory
     *
     * @return void
     */
    public function testMapCoreFileToCategory(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory(CAKE . 'basics.php');
        $this->assertSame('core', $return);

        $return = $this->Shell->mapFileToCategory(CAKE . 'Core/App.php');
        $this->assertSame('core', $return);

        $return = $this->Shell->mapFileToCategory(CAKE . 'Some/Deeply/Nested/Structure.php');
        $this->assertSame('core', $return);
    }

    /**
     * testMapCoreFileToCase
     *
     * basics.php is a slightly special case - it's the only file in the core with a test that isn't Capitalized
     *
     * @return void
     */
    public function testMapCoreFileToCase(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCase(CAKE . 'basics.php', 'core');
        $this->assertSame('Basics', $return);

        $return = $this->Shell->mapFileToCase(CAKE . 'Core/App.php', 'core');
        $this->assertSame('Core/App', $return);

        $return = $this->Shell->mapFileToCase(CAKE . 'Some/Deeply/Nested/Structure.php', 'core', false);
        $this->assertSame('Some/Deeply/Nested/Structure', $return);
    }

    /**
     * testMapAppFileToCategory
     *
     * @return void
     */
    public function testMapAppFileToCategory(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory(APP . 'Controller/ExampleController.php');
        $this->assertSame('app', $return);

        $return = $this->Shell->mapFileToCategory(APP . 'My/File/Is/Here.php');
        $this->assertSame('app', $return);
    }

    /**
     * testMapAppFileToCase
     *
     * @return void
     */
    public function testMapAppFileToCase(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCase(APP . 'Controller/ExampleController.php', 'app', false);
        $this->assertSame('Controller/ExampleController', $return);

        $return = $this->Shell->mapFileToCase(APP . 'My/File/Is/Here.php', 'app', false);
        $this->assertSame('My/File/Is/Here', $return);
    }

    /**
     * testMapPluginFileToCategory
     *
     * @return void
     */
    public function testMapPluginFileToCategory(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory(APP . 'Plugin/awesome/Controller/ExampleController.php');
        $this->assertSame('awesome', $return);

        $return = $this->Shell->mapFileToCategory(dirname(CAKE) . 'plugins/awesome/Controller/ExampleController.php');
        $this->assertSame('awesome', $return);
    }

    /**
     * testMapPluginFileToCase
     *
     * @return void
     */
    public function testMapPluginFileToCase(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCase(APP . 'Plugin/awesome/Controller/ExampleController.php', 'awesome', false);
        $this->assertSame('Controller/ExampleController', $return);

        $return = $this->Shell->mapFileToCase(dirname(CAKE) . 'plugins/awesome/Controller/ExampleController.php', 'awesome', false);
        $this->assertSame('Controller/ExampleController', $return);
    }

    /**
     * testMapCoreTestToCategory
     *
     * @return void
     */
    public function testMapCoreTestToCategory(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory('tests/TestCase/BasicsTest.php');
        $this->assertSame('core', $return);

        $return = $this->Shell->mapFileToCategory('tests/TestCase/BasicsTest.php');
        $this->assertSame('core', $return);

        $return = $this->Shell->mapFileToCategory('tests/TestCase/Some/Deeply/Nested/StructureTest.php');
        $this->assertSame('app', $return);
    }

    /**
     * testMapCoreTestToCase
     *
     * basics.php is a slightly special case - it's the only file in the core with a test that isn't Capitalized
     *
     * @return void
     */
    public function testMapCoreTestToCase(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCase('tests/TestCase/BasicsTest.php', 'core');
        $this->assertSame('Basics', $return);

        $return = $this->Shell->mapFileToCase('tests/TestCase/Core/AppTest.php', 'core');
        $this->assertSame('Core/App', $return);

        $return = $this->Shell->mapFileToCase('tests/TestCase/Some/Deeply/Nested/StructureTest.php', 'core', false);
        $this->assertSame('Some/Deeply/Nested/Structure', $return);
    }

    /**
     * testMapAppTestToCategory
     *
     * @return void
     */
    public function testMapAppTestToCategory(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory(APP . 'Test/Case/Controller/ExampleControllerTest.php');
        $this->assertSame('app', $return);

        $return = $this->Shell->mapFileToCategory(APP . 'Test/Case/My/File/Is/HereTest.php');
        $this->assertSame('app', $return);
    }

    /**
     * testMapAppTestToCase
     *
     * @return void
     */
    public function testMapAppTestToCase(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCase(APP . 'Test/Case/Controller/ExampleControllerTest.php', 'app', false);
        $this->assertSame('Controller/ExampleController', $return);

        $return = $this->Shell->mapFileToCase(APP . 'Test/Case/My/File/Is/HereTest.php', 'app', false);
        $this->assertSame('My/File/Is/Here', $return);
    }

    /**
     * testMapPluginTestToCategory
     *
     * @return void
     */
    public function testMapPluginTestToCategory(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory(APP . 'Plugin/awesome/Test/Case/Controller/ExampleControllerTest.php');
        $this->assertSame('awesome', $return);

        $return = $this->Shell->mapFileToCategory(dirname(CAKE) . 'plugins/awesome/Test/Case/Controller/ExampleControllerTest.php');
        $this->assertSame('awesome', $return);
    }

    /**
     * testMapPluginTestToCase
     *
     * @return void
     */
    public function testMapPluginTestToCase(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCase(APP . 'Plugin/awesome/Test/Case/Controller/ExampleControllerTest.php', 'awesome', false);
        $this->assertSame('Controller/ExampleController', $return);

        $return = $this->Shell->mapFileToCase(dirname(CAKE) . 'plugins/awesome/Test/Case/Controller/ExampleControllerTest.php', 'awesome', false);
        $this->assertSame('Controller/ExampleController', $return);
    }

    /**
     * testMapNotTestToNothing
     *
     * @return void
     */
    public function testMapNotTestToNothing(): void
    {
        $this->Shell->startup();

        $return = $this->Shell->mapFileToCategory(APP . 'Test/Case/NotATestFile.php');
        $this->assertSame('app', $return);

        $return = $this->Shell->mapFileToCase(APP . 'Test/Case/NotATestFile.php', false, false);
        $this->assertFalse($return);

        $return = $this->Shell->mapFileToCategory(APP . 'Test/Fixture/SomeTest.php');
        $this->assertSame('app', $return);

        $return = $this->Shell->mapFileToCase(APP . 'Test/Fixture/SomeTest.php', false, false);
        $this->assertFalse($return);
    }

    /**
     * test available list of test cases for an empty category
     *
     * @return void
     */
    public function testAvailableWithEmptyList(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['unexistant-category'];

        $this->Shell->expects($this->exactly(2))
            ->method('out')
            ->withConsecutive(
                [__d('cake_console', "No test cases available \n\n")],
                [$this->anything()],
            );

        $this->Shell->OptionParser->expects($this->once())->method('help');
        $this->Shell->available();
    }

    /**
     * test available list of test cases for core category
     *
     * @return void
     */
    public function testAvailableCoreCategory(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['core'];

        $outputs = [];
        $this->Shell
            ->expects($this->atLeast(3))
            ->method('out')
            ->willReturnCallback(function ($value) use (&$outputs) {
                $outputs[] = $value;

                return 0;
            });

        $this->Shell
            ->expects($this->once())
            ->method('in')
            ->with(__d('cake_console', 'What test case would you like to run?'), null, 'q')
            ->will($this->returnValue('1'));

        $this->Shell
            ->expects($this->once())
            ->method('_run');
        $this->Shell->available();

        $this->assertEquals(['core', 'AllBehaviors'], $this->Shell->args);
        $this->assertEquals('Core Test Cases:', $outputs[0]);
        $this->assertStringContainsString('[1]', $outputs[1]);
        $this->assertStringContainsString('[2]', $outputs[2]);
    }

    /**
     * Tests that correct option for test runner are passed
     *
     * @return void
     */
    public function testRunnerOptions(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['core', 'Basics'];
        $this->Shell->params = ['filter' => 'myFilter', 'colors' => true, 'verbose' => true];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Basics'],
                ['--filter', 'myFilter', '--colors', '--verbose'],
            );
        $this->Shell->main();
    }

    /**
     * Tests that the 'quiet' parameter gets swallowed before calling PHPUnit
     *
     * @return void
     */
    public function testRunnerOptionsQuiet(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['core', 'Basics'];
        $this->Shell->params = ['quiet' => true];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Basics'],
                ['--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Tests that the '--directive' parameter change to '-d' before calling PHPUnit
     *
     * @return void
     */
    public function testRunnerOptionsDirective(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['core', 'Basics'];
        $this->Shell->params = ['directive' => 'memory_limit=128M'];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Basics'],
                ['-d', 'memory_limit=128M', '--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Gets the temporary plugins directory used by the tests.
     *
     * @return string
     */
    protected function _pluginsPath(): string
    {
        return TMP . 'test_shell_plugins' . DS;
    }

    /**
     * Creates a temporary plugin with one source file and its test case.
     *
     * @param string $plugin The plugin name.
     * @param string $testDir The test case directory, relative to the plugin.
     * @param string $sourceDir The source directory, relative to the plugin.
     * @return string The path of the source file
     */
    protected function _createPlugin(string $plugin, string $testDir, string $sourceDir = ''): string
    {
        $path = $this->_pluginsPath() . $plugin . DS;
        $source = $path . ($sourceDir !== '' ? $sourceDir . DS : '');
        new Folder($source . 'Model', true);
        new Folder($path . $testDir . DS . 'Model', true);
        file_put_contents($source . 'Model' . DS . 'Thing.php', "<?php\n");
        file_put_contents($path . $testDir . DS . 'Model' . DS . 'ThingTest.php', "<?php\n");
        file_put_contents($path . $testDir . DS . 'Model' . DS . 'OtherTest.php', "<?php\n");

        App::build(['Plugin' => [$this->_pluginsPath()]], App::RESET);

        return $source . 'Model' . DS . 'Thing.php';
    }

    /**
     * Test that initialize() sets up the dispatcher
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $Shell = new TestShell($out, $out, $in);
        $Shell->initialize();

        $property = new ReflectionProperty(TestShell::class, '_dispatcher');
        if (PHP_VERSION_ID < 80100) {
            $property->setAccessible(true);
        }
        $this->assertInstanceOf(CakeTestSuiteDispatcher::class, $property->getValue($Shell));
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Shell->getOptionParser();
        $this->assertInstanceOf(ConsoleOptionParser::class, $parser);

        $arguments = $parser->arguments();
        $this->assertSame('category', $arguments[0]->name());
        $this->assertSame('file', $arguments[1]->name());

        $options = $parser->options();
        foreach (['log-junit', 'coverage-html', 'filter', 'group', 'no-colors', 'stop-on-failure', 'directive', 'fixture'] as $name) {
            $this->assertArrayHasKey($name, $options);
        }
        $this->assertSame('d', $options['directive']->short());
        $this->assertTrue($options['no-colors']->isBoolean());
        $this->assertFalse($options['filter']->isBoolean());

        $result = $parser->parse(['core', 'Basics', '--filter', 'testFoo', '-d', 'memory_limit=1G', '--no-colors']);
        $this->assertSame(['core', 'Basics'], $result[1]);
        $this->assertSame('testFoo', $result[0]['filter']);
        $this->assertSame('memory_limit=1G', $result[0]['directive']);
        $this->assertTrue($result[0]['no-colors']);
        $this->assertFalse($result[0]['log-junit']);
    }

    /**
     * Test that false options are skipped and that no-colors removes the colors option
     *
     * @return void
     */
    public function testRunnerOptionsNoColors(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['core', 'Basics'];
        $this->Shell->params = [
            'help' => false,
            'log-junit' => false,
            'stop-on-failure' => true,
            'no-colors' => true,
            'colors' => true,
            'log-json' => '/tmp/out.json',
        ];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Basics'],
                ['--stop-on-failure', '--log-json', '/tmp/out.json'],
            );
        $this->Shell->main();
    }

    /**
     * Test that a test file passed as the first argument is mapped to its category and case
     *
     * @return void
     */
    public function testMainWithCoreTestFile(): void
    {
        $this->Shell->startup();
        $this->Shell->args = [CORE_TESTS . DS . 'TestCase' . DS . 'Core' . DS . 'AppTest.php'];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Core/App'],
                ['--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Test that a core source file passed as the first argument is mapped to its test case
     *
     * @return void
     */
    public function testMainWithCoreSourceFile(): void
    {
        $this->Shell->startup();
        $this->Shell->args = [CAKE . 'Core' . DS . 'App.php'];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => null, 'core' => true, 'output' => 'text', 'case' => 'Core/App'],
                ['--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Test the app category
     *
     * @return void
     */
    public function testMainAppCategory(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['app', 'Model/Post'];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => true, 'plugin' => null, 'core' => false, 'output' => 'text', 'case' => 'Model/Post'],
                ['--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Test that a plugin source file is mapped to a test case in the tests/TestCase layout
     *
     * @return void
     */
    public function testMainWithPluginFileTestsTestCaseLayout(): void
    {
        $file = $this->_createPlugin('CovPlugin', 'tests' . DS . 'TestCase');

        $this->Shell->startup();
        $this->Shell->args = [$file];

        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => 'CovPlugin', 'core' => false, 'output' => 'text', 'case' => 'Model/Thing'],
                ['--colors'],
            );
        $this->Shell->main();
    }

    /**
     * Test that a plugin source file is mapped to a test case in the Test/Case layout
     *
     * @return void
     */
    public function testMapPluginFileToCaseTestCaseLayout(): void
    {
        $file = $this->_createPlugin('LegacyCovPlugin', 'Test' . DS . 'Case');

        $this->assertSame('LegacyCovPlugin', $this->Shell->mapFileToCategory($file));
        $this->assertSame('Model/Thing', $this->Shell->mapFileToCase($file, 'LegacyCovPlugin'));
    }

    /**
     * Test that a source file in the src directory of a plugin is mapped to a test case in tests/TestCase
     *
     * @return void
     */
    public function testMapPluginSrcFileToCase(): void
    {
        $file = $this->_createPlugin('SrcCovPlugin', 'tests' . DS . 'TestCase', 'src');

        $this->assertSame('SrcCovPlugin', $this->Shell->mapFileToCategory($file));
        $this->assertSame('Model/Thing', $this->Shell->mapFileToCase($file, 'SrcCovPlugin'));
    }

    /**
     * Test that a plugin test file is mapped to its test case
     *
     * @return void
     */
    public function testMapPluginTestFileToCase(): void
    {
        $this->_createPlugin('CovPlugin', 'tests' . DS . 'TestCase');
        $file = $this->_pluginsPath() . 'CovPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'ThingTest.php';

        $this->assertSame('CovPlugin', $this->Shell->mapFileToCategory($file));
        $this->assertSame('Model/Thing', $this->Shell->mapFileToCase($file, 'CovPlugin'));
    }

    /**
     * Test that a missing core test case throws an exception
     *
     * @return void
     */
    public function testMapCoreFileToCaseMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test case ' . CORE_TESTS . '/TestCase/Some/Missing/ThingTest.php not found');

        $this->Shell->mapFileToCase(CAKE . 'Some' . DS . 'Missing' . DS . 'Thing.php', 'core');
    }

    /**
     * Test that a missing plugin test case throws an exception
     *
     * Both the tests/TestCase and the Test/Case layouts are tried. The message
     * names the path that was tried last, the one of the Test/Case layout.
     *
     * @return void
     */
    public function testMapPluginFileToCaseMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Test case ' . APP . 'Plugin/awesome/Test/Case/Controller/ExampleControllerTest.php not found');

        $this->Shell->mapFileToCase(APP . 'Plugin/awesome/Controller/ExampleController.php', 'awesome');
    }

    /**
     * Test that a missing app test case throws an exception
     *
     * @return void
     */
    public function testMapAppFileToCaseMissing(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/^Test case .*Controller\/MissingExampleControllerTest\.php not found$/');

        $this->Shell->mapFileToCase(APP . 'Controller/MissingExampleController.php', 'app');
    }

    /**
     * Test that a test file without a test case name throws an exception
     *
     * @return void
     */
    public function testMapFileToCaseWithoutName(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('cannot be run via this shell');

        $this->Shell->mapFileToCase('tests/TestCase/Test.php', 'app');
    }

    /**
     * Test that main() without arguments shows that no test cases are available
     *
     * @return void
     */
    public function testMainWithoutArguments(): void
    {
        $this->Shell->startup();
        $this->Shell->args = [];

        $outputs = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($value = '') use (&$outputs) {
                $outputs[] = $value;

                return 0;
            });
        $this->Shell->OptionParser->expects($this->once())->method('help')->will($this->returnValue('help text'));
        $this->Shell->expects($this->never())->method('_run');

        $this->Shell->main();

        $this->assertSame(['CakePHP Test Shell', "No test cases available \n\n", 'help text'], $outputs);
    }

    /**
     * Test that main() with only a category lists the test cases and quits
     *
     * @return void
     */
    public function testMainListsPluginTestCasesAndQuits(): void
    {
        $this->_createPlugin('CovPlugin', 'tests' . DS . 'TestCase');

        $this->Shell->startup();
        $this->Shell->args = ['CovPlugin'];

        $outputs = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($value = '') use (&$outputs) {
                $outputs[] = $value;

                return 0;
            });
        $this->Shell->expects($this->exactly(2))
            ->method('in')
            ->will($this->onConsecutiveCalls('99', 'q'));
        $this->Shell->expects($this->never())->method('_run');

        $this->Shell->main();

        $this->assertSame(
            ['CakePHP Test Shell', 'CovPlugin Test Cases:', '[1] Model/Other', '[2] Model/Thing'],
            $outputs,
        );
        $this->assertSame(['CovPlugin'], $this->Shell->args);
    }

    /**
     * Test choosing a test case by its name
     *
     * @return void
     */
    public function testAvailableChooseByName(): void
    {
        $this->_createPlugin('CovPlugin', 'tests' . DS . 'TestCase');

        $this->Shell->startup();
        $this->Shell->args = ['CovPlugin'];

        $this->Shell->expects($this->once())
            ->method('in')
            ->will($this->returnValue('Model/Thing'));
        $this->Shell->expects($this->once())->method('_run')
            ->with(
                ['app' => false, 'plugin' => 'CovPlugin', 'core' => false, 'output' => 'text', 'case' => 'Model/Thing'],
                ['--colors'],
            );

        $this->Shell->available();

        $this->assertSame(['CovPlugin', 'Model/Thing'], $this->Shell->args);
    }

    /**
     * Test the available list for the app category
     *
     * @return void
     */
    public function testAvailableAppCategory(): void
    {
        $this->Shell->startup();
        $this->Shell->args = ['app'];

        $this->Shell->expects($this->exactly(2))
            ->method('out')
            ->withConsecutive(
                ["No test cases available \n\n"],
                ['app help'],
            );
        $this->Shell->OptionParser->expects($this->once())->method('help')->will($this->returnValue('app help'));

        $this->Shell->available();
    }
}
