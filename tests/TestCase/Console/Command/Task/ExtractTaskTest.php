<?php
/**
 * ExtractTaskTest file
 *
 * Test Case for i18n extraction shell task
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command\Task;

use Cake\Console\Command\Task\ExtractTask;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Folder;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use ReflectionProperty;

/**
 * ExtractTaskTest class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class ExtractTaskTest extends CakeTestCase
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
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Task = $this->getMock(
            ExtractTask::class,
            ['in', 'out', 'err', '_stop'],
            [$out, $out, $in],
        );
        $this->path = TMP . 'tests' . DS . 'extract_task_test';
        new Folder($this->path . DS . 'locale', true);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Task);

        $Folder = new Folder($this->path);
        $Folder->delete();
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * testExecute method
     *
     * @return void
     */
    public function testExecute(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->execute();
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));
        $result = file_get_contents($this->path . DS . 'default.pot');

        $this->assertFalse(file_exists($this->path . DS . 'cake.pot'));

        $pattern = '/"Content-Type\: text\/plain; charset\=utf-8/';
        $this->assertMatchesRegularExpression($pattern, $result);
        $pattern = '/"Content-Transfer-Encoding\: 8bit/';
        $this->assertMatchesRegularExpression($pattern, $result);
        $pattern = '/"Plural-Forms\: nplurals\=INTEGER; plural\=EXPRESSION;/';
        $this->assertMatchesRegularExpression($pattern, $result);

        // home.ctp
        $pattern = '/msgid "Your tmp directory is writable."\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Your tmp directory is NOT writable."\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "The %s is being used for caching. To change the config edit ';
        $pattern .= '%s\/core.php "\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Your cache is NOT working. Please check ';
        $pattern .= 'the settings in %s\/core.php"\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Your database configuration file is present."\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Your database configuration file is NOT present."\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Rename config\/database.php.default to ';
        $pattern .= 'config\/database.php"\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Cake is able to connect to the database."\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Cake is NOT able to connect to the database."\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "Editing this Page"\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "To change the content of this page, create: APP\/views\/pages\/home\.ctp/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/To change its layout, create: APP\/views\/layouts\/default\.ctp\./s';
        $this->assertMatchesRegularExpression($pattern, $result);

        // extract.ctp
        $pattern = '/\#: extract\.ctp:15;6\n';
        $pattern .= 'msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/msgid "You have %d new message."\nmsgstr ""/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result, 'No duplicate msgid');

        $pattern = '/\#: extract\.ctp:7\n';
        $pattern .= 'msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/\#: extract\.ctp:14\n';
        $pattern .= '\#: home\.ctp:67\n';
        $pattern .= 'msgid "Editing this Page"\nmsgstr ""/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $pattern = '/\#: extract\.ctp:22\nmsgid "';
        $pattern .= 'Hot features!';
        $pattern .= '\\\n - No Configuration: Set-up the database and let the magic begin';
        $pattern .= '\\\n - Extremely Simple: Just look at the name...It\'s Cake';
        $pattern .= '\\\n - Active, Friendly Community: Join us #cakephp on IRC. We\'d love to help you get started';
        $pattern .= '"\nmsgstr ""/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $this->assertStringContainsString('msgid "double \\"quoted\\""', $result, 'Strings with quotes not handled correctly');
        $this->assertStringContainsString("msgid \"single 'quoted'\"", $result, 'Strings with quotes not handled correctly');

        $pattern = '/\#: extract\.ctp:36\nmsgid "letter"/';
        $this->assertMatchesRegularExpression($pattern, $result, 'Strings with context should not overwrite strings without context');

        $pattern = '/\#: extract\.ctp:37;39\nmsgctxt "A"\nmsgid "letter"/';
        $this->assertMatchesRegularExpression($pattern, $result, 'Should contain string with context "A"');

        $pattern = '/\#: extract\.ctp:38\nmsgctxt "B"\nmsgid "letter"/';
        $this->assertMatchesRegularExpression($pattern, $result, 'Should contain string with context "B"');

        $pattern = '/\#: extract\.ctp:40\nmsgid "%d letter"\nmsgid_plural "%d letters"/';
        $this->assertMatchesRegularExpression($pattern, $result, 'Plural strings with context should not overwrite strings without context');

        $pattern = '/\#: extract\.ctp:41\nmsgctxt "A"\nmsgid "%d letter"\nmsgid_plural "%d letters"/';
        $this->assertMatchesRegularExpression($pattern, $result, 'Should contain plural string with context "A"');

        // extract.ctp - reading the domain.pot
        $result = file_get_contents($this->path . DS . 'domain.pot');

        $pattern = '/msgid "You have %d new message."\nmsgid_plural "You have %d new messages."/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
        $pattern = '/msgid "You deleted %d message."\nmsgid_plural "You deleted %d messages."/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/msgid "You have %d new message \(domain\)."\nmsgid_plural "You have %d new messages \(domain\)."/';
        $this->assertMatchesRegularExpression($pattern, $result);
        $pattern = '/msgid "You deleted %d message \(domain\)."\nmsgid_plural "You deleted %d messages \(domain\)."/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    /**
     * testExtractCategory method
     *
     * @return void
     */
    public function testExtractCategory(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->execute();
        $this->assertTrue(file_exists($this->path . DS . 'LC_NUMERIC' . DS . 'default.pot'));
        $this->assertFalse(file_exists($this->path . DS . 'LC_TIME' . DS . 'default.pot'));

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\#: .*extract\.ctp:31\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/\#: .*extract\.ctp:33\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * testExtractWithoutLocations method
     *
     * @return void
     */
    public function testExtractWithoutLocations(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Pages';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->params['no-location'] = true;

        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->execute();
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\n\#: .*\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * test exclusions
     *
     * @return void
     */
    public function testExtractWithExclude(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS . 'templates';
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude'] = 'Pages,Layouts';
        $this->Task->params['extract-core'] = 'no';

        $this->Task->expects($this->any())->method('in')
            ->will($this->returnValue('y'));

        $this->Task->execute();
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));
        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/\#: .*extract\.ctp:6\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/\#: .*default\.ctp:26\n/';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * test extract can read more than one path.
     *
     * @return void
     */
    public function testExtractMultiplePaths(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] =
            CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Pages,' .
            CORE_TESTS . DS . 'test_app' . DS . 'templates' . DS . 'Posts';

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->never())->method('_stop');
        $this->Task->execute();

        $result = file_get_contents($this->path . DS . 'default.pot');

        $pattern = '/msgid "Add User"/';
        $this->assertMatchesRegularExpression($pattern, $result);
    }

    /**
     * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask
     *
     * @return void
     */
    public function testExtractExcludePlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        $this->out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $this->in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $this->Task = $this->getMock(
            ExtractTask::class,
            ['_isExtractingApp', '_extractValidationMessages', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude-plugins'] = true;

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertDoesNotMatchRegularExpression('#TestPlugin#', $result);
    }

    /**
     * Test that is possible to extract messages form a single plugin
     *
     * @return void
     */
    public function testExtractPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);

        $this->out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $this->in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $this->Task = $this->getMock(
            ExtractTask::class,
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['plugin'] = 'TestPlugin';

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertDoesNotMatchRegularExpression('#Pages#', $result);
        $this->assertStringContainsString('translate.ctp:1', $result);
        $this->assertStringContainsString('This is a translatable string', $result);
        $this->assertStringContainsString('I can haz plugin model validation message', $result);
    }

    /**
     * Tests that the task will inspect application models and extract the validation messages from them
     *
     * @return void
     */
    public function testExtractModelValidation(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        $this->out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $this->in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $this->Task = $this->getMock(
            ExtractTask::class,
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task
            ->expects($this->exactly(2))
            ->method('_isExtractingApp')
            ->will($this->returnValue(true));

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['exclude-plugins'] = true;
        $this->Task->params['ignore-model-validation'] = false;

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertStringContainsString('Model/PersisterOne.php:validation for field title', $result);
        $this->assertStringContainsString('Model/PersisterOne.php:validation for field body', $result);
        $this->assertStringContainsString('msgid "Post title is required"', $result);
        $this->assertStringContainsString('msgid "You may enter up to %s chars (minimum is %s chars)"', $result);
        $this->assertStringContainsString('msgid "Post body is required"', $result);
        $this->assertStringContainsString('msgid "Post body is super required"', $result);
        $this->assertStringContainsString('msgid "double \\"quoted\\" validation"', $result, 'Strings with quotes not handled correctly');
        $this->assertStringContainsString("msgid \"single 'quoted' validation\"", $result, 'Strings with quotes not handled correctly');
    }

    /**
     *  Tests that the task will inspect application models and extract the validation messages from them
     *  while using a custom validation domain for the messages set on the model itself
     *
     * @return void
     */
    public function testExtractModelValidationWithDomainInModel(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS . 'TestPlugin' . DS . 'src' . DS . 'Model' . DS],
        ]);
        // The models of the application are the namespaced models of the plugin here
        Configure::write('App.namespace', 'TestPlugin');
        $this->out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $this->in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $this->Task = $this->getMock(
            ExtractTask::class,
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task
            ->expects($this->exactly(2))
            ->method('_isExtractingApp')
            ->will($this->returnValue(true));

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['exclude-plugins'] = true;
        $this->Task->params['ignore-model-validation'] = false;

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'test_plugin.pot');
        $this->assertStringContainsString('plugins/TestPlugin/src/Model/TestPluginPost.php:validation for field title', $result);
        $this->assertStringContainsString('plugins/TestPlugin/src/Model/TestPluginPost.php:validation for field body', $result);
        $this->assertStringContainsString('msgid "Post title is required"', $result);
        $this->assertStringContainsString('msgid "Post body is required"', $result);
        $this->assertStringContainsString('msgid "Post body is super required"', $result);
    }

    /**
     *  Test that the extract shell can obtain validation messages from models inside a specific plugin
     *
     * @return void
     */
    public function testExtractModelValidationInPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        $this->out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $this->in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $this->Task = $this->getMock(
            ExtractTask::class,
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['ignore-model-validation'] = false;
        $this->Task->params['plugin'] = 'TestPlugin';

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'test_plugin.pot');
        $this->assertStringContainsString('Model/TestPluginPost.php:validation for field title', $result);
        $this->assertStringContainsString('Model/TestPluginPost.php:validation for field body', $result);
        $this->assertStringContainsString('msgid "Post title is required"', $result);
        $this->assertStringContainsString('msgid "Post body is required"', $result);
        $this->assertStringContainsString('msgid "Post body is super required"', $result);
        $this->assertStringNotContainsString('Plugin/TestPlugin/Model/TestPluginPost.php:validation for field title', $result);
    }

    /**
     *  Test that the extract shell overwrites existing files with the overwrite parameter
     *
     * @return void
     */
    public function testExtractOverwrite(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['overwrite'] = true;

        file_put_contents($this->path . DS . 'default.pot', 'will be overwritten');
        $this->assertTrue(file_exists($this->path . DS . 'default.pot'));
        $original = file_get_contents($this->path . DS . 'default.pot');

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertNotEquals($original, $result);
    }

    /**
     *  Test that the extract shell scans the core libs
     *
     * @return void
     */
    public function testExtractCore(): void
    {
        $this->Task->interactive = false;

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'yes';

        $this->Task->execute();
        $this->assertTrue(file_exists($this->path . DS . 'cake.pot'));
        $result = file_get_contents($this->path . DS . 'cake.pot');

        $pattern = '/msgid "Yesterday, %s"\nmsgstr ""\n/';
        $this->assertMatchesRegularExpression($pattern, $result);

        $this->assertTrue(file_exists($this->path . DS . 'cake_dev.pot'));
        $result = file_get_contents($this->path . DS . 'cake_dev.pot');

        $pattern = '/#: Console\/Templates\//';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);

        $pattern = '/#: Test\//';
        $this->assertDoesNotMatchRegularExpression($pattern, $result);
    }

    /**
     * Test that a literal count argument of __dcn() does not overwrite the token position
     * used while parsing, so following calls are still extracted.
     *
     * @return void
     */
    public function testParseDcnWithLiteralCount(): void
    {
        $code = "<?php\n" .
            "__dcn('domain', 'first singular', 'first plural', 1000, 6);\n" .
            "__dcn('domain', 'second singular', 'second plural', 1, 6);\n";

        $tokens = new ReflectionProperty(ExtractTask::class, '_tokens');
        if (PHP_VERSION_ID < 80100) {
            $tokens->setAccessible(true);
        }
        $tokens->setValue($this->Task, array_values(array_filter(
            token_get_all($code),
            fn($token) => !is_array($token) || ($token[0] !== T_WHITESPACE && $token[0] !== T_INLINE_HTML),
        )));
        $file = new ReflectionProperty(ExtractTask::class, '_file');
        if (PHP_VERSION_ID < 80100) {
            $file->setAccessible(true);
        }
        $file->setValue($this->Task, 'test.php');

        $parse = new ReflectionMethod(ExtractTask::class, '_parse');
        if (PHP_VERSION_ID < 80100) {
            $parse->setAccessible(true);
        }
        $parse->invoke($this->Task, '__dcn', ['domain', 'singular', 'plural', 'count', 'category']);

        $translations = new ReflectionProperty(ExtractTask::class, '_translations');
        if (PHP_VERSION_ID < 80100) {
            $translations->setAccessible(true);
        }
        $result = $translations->getValue($this->Task);

        $this->assertArrayHasKey('first singular', $result['LC_MESSAGES']['domain']);
        $this->assertArrayHasKey('second singular', $result['LC_MESSAGES']['domain']);
        $this->assertSame('second plural', $result['LC_MESSAGES']['domain']['second singular']['']['msgid_plural']);
    }

    /**
     * Writes a PHP source file for the extraction into the source directory
     *
     * @param string $name The file name
     * @param string $code The file contents
     * @return string The source directory
     */
    protected function _writeSource(string $name, string $code): string
    {
        $source = $this->path . DS . 'source';
        new Folder($source, true);
        file_put_contents($source . DS . $name, $code);

        return $source;
    }

    /**
     * Creates a task mock that records the messages passed to err()
     *
     * @param array $errors The recorded messages
     * @return ExtractTask|MockObject
     */
    protected function _taskRecordingErrors(array &$errors): ExtractTask|MockObject
    {
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $task = $this->getMock(
            ExtractTask::class,
            ['in', 'out', 'err', '_stop'],
            [$out, $out, $in],
        );
        $task->expects($this->any())
            ->method('err')
            ->willReturnCallback(function ($message = null) use (&$errors) {
                $errors[] = $message;

                return 0;
            });

        return $task;
    }

    /**
     * Test that the paths are asked for when neither the paths nor the plugin are given
     *
     * @return void
     */
    public function testExecuteAsksForPaths(): void
    {
        $source = $this->_writeSource('interactive.php', "<?php\n__('Interactive path string');\n");
        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->exactly(4))
            ->method('in')
            ->willReturnOnConsecutiveCalls('d', $this->path . DS . 'missing', $source, 'd');
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->execute();

        $this->assertCount(2, $errors);
        $this->assertStringContainsString('No directories selected.', $errors[0]);
        $this->assertStringContainsString('The directory path you supplied was not found.', $errors[1]);
        $result = file_get_contents($this->path . DS . 'locale' . DS . 'default.pot');
        $this->assertStringContainsString('#: interactive.php:2', $result);
        $this->assertStringContainsString('msgid "Interactive path string"', $result);
    }

    /**
     * Test that quitting the path selection aborts the extraction
     *
     * @return void
     */
    public function testExecuteQuitPaths(): void
    {
        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->once())->method('in')->willReturn('q');
        $this->Task->expects($this->once())->method('_stop');

        $this->Task->execute();

        $this->assertSame(['Extract Aborted'], $errors);
        $this->assertFileDoesNotExist($this->path . DS . 'locale' . DS . 'default.pot');
    }

    /**
     * Test that the output directory is asked for when it is not given
     *
     * @return void
     */
    public function testExecuteAsksForOutput(): void
    {
        $source = $this->_writeSource('output.php', "<?php\n__('Output path string');\n");
        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->params['paths'] = $source;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $defaults = [];
        $responses = [$this->path . DS . 'missing', $this->path . DS . 'locale'];
        $this->Task->expects($this->exactly(2))
            ->method('in')
            ->willReturnCallback(function ($prompt, $options = null, $default = null) use (&$defaults, &$responses) {
                $defaults[] = $default;

                return array_shift($responses);
            });
        $this->Task->expects($this->never())->method('_stop');

        $this->Task->execute();

        $this->assertSame($source . DS . 'Locale', $defaults[0]);
        $this->assertSame(['The directory path you supplied was not found. Please try again.'], $errors);
        $result = file_get_contents($this->path . DS . 'locale' . DS . 'default.pot');
        $this->assertStringContainsString('msgid "Output path string"', $result);
    }

    /**
     * Test that quitting the output selection aborts the extraction
     *
     * @return void
     */
    public function testExecuteQuitOutput(): void
    {
        $source = $this->_writeSource('quit.php', "<?php\n__('Quit string');\n");
        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->params['paths'] = $source;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->once())->method('in')->willReturn('Q');
        $this->Task->expects($this->once())->method('_stop');

        $this->Task->execute();

        $this->assertSame(['Extract Aborted'], $errors);
        $this->assertFileDoesNotExist($source . DS . 'default.pot');
        $this->assertFileDoesNotExist($this->path . DS . 'locale' . DS . 'default.pot');
    }

    /**
     * Test that an output directory that does not exist stops the extraction
     *
     * @return void
     */
    public function testExecuteUnusableOutput(): void
    {
        $source = $this->_writeSource('unusable.php', "<?php\n__('Unusable string');\n");
        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->params['paths'] = $source;
        $this->Task->params['output'] = $this->path . DS . 'missing';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->never())->method('in');
        $this->Task->expects($this->once())->method('_stop');

        $this->Task->execute();

        $this->assertCount(1, $errors);
        $this->assertStringContainsString(
            'The output directory ' . $this->path . DS . 'missing' . DS . ' was not found or writable.',
            $errors[0],
        );
        $this->assertDirectoryDoesNotExist($this->path . DS . 'missing');
    }

    /**
     * Test that the plugin option loads the plugin and writes into the Locale directory of the plugin
     *
     * @return void
     */
    public function testExecutePluginOutputsToPluginLocale(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        $this->assertFalse(CakePlugin::loaded('TestPlugin'));

        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->params['plugin'] = 'test_plugin';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->expects($this->never())->method('in');
        // The test plugin has no Locale directory, so the extraction stops before writing anything.
        $this->Task->expects($this->once())->method('_stop');

        $this->Task->execute();

        $this->assertTrue(CakePlugin::loaded('TestPlugin'));
        $this->assertSame('TestPlugin', $this->Task->params['plugin']);
        $expected = CakePlugin::path('TestPlugin') . DS . 'Locale' . DS;
        $this->assertCount(1, $errors);
        $this->assertStringContainsString('The output directory ' . $expected . ' was not found or writable.', $errors[0]);
    }

    /**
     * Test that the files option limits the extraction to the given files
     *
     * @return void
     */
    public function testExecuteFiles(): void
    {
        $this->_writeSource('included.php', "<?php\n__('Included string');\n");
        $source = $this->_writeSource('skipped.php', "<?php\n__('Skipped string');\n");

        $this->Task->interactive = false;
        $this->Task->params['paths'] = $source;
        $this->Task->params['files'] = $source . DS . 'included.php';
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->expects($this->never())->method('err');
        $this->Task->expects($this->never())->method('in');

        $this->Task->execute();

        $result = file_get_contents($this->path . DS . 'locale' . DS . 'default.pot');
        $this->assertStringContainsString('msgid "Included string"', $result);
        $this->assertStringNotContainsString('Skipped string', $result);
    }

    /**
     * Test that the exclude option accepts absolute paths and relative path segments
     *
     * @return void
     */
    public function testExecuteExcludeSegments(): void
    {
        $this->_writeSource('kept.php', "<?php\n__('Kept string');\n");
        new Folder($this->path . DS . 'source' . DS . 'vendors' . DS . 'lib', true);
        new Folder($this->path . DS . 'source' . DS . 'generated', true);
        $source = $this->_writeSource('vendors' . DS . 'lib' . DS . 'vendor.php', "<?php\n__('Vendor string');\n");
        $this->_writeSource('generated' . DS . 'generated.php', "<?php\n__('Generated string');\n");

        $this->Task->interactive = false;
        $this->Task->params['paths'] = $source;
        $this->Task->params['exclude'] = 'vendors/lib,' . realpath($source) . DS . 'generated';
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->expects($this->never())->method('err');

        $this->Task->execute();

        $result = file_get_contents($this->path . DS . 'locale' . DS . 'default.pot');
        $this->assertStringContainsString('msgid "Kept string"', $result);
        $this->assertStringNotContainsString('Vendor string', $result);
        $this->assertStringNotContainsString('Generated string', $result);
    }

    /**
     * Test that the strings of all domains and categories are merged into default.pot
     *
     * @return void
     */
    public function testExecuteMerge(): void
    {
        $source = $this->_writeSource(
            'merge.php',
            "<?php\n__('Default string');\n__d('other', 'Other domain string');\n__c('Numeric string', 4);\n",
        );

        $this->Task->interactive = false;
        $this->Task->params['paths'] = $source;
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'yes';
        $this->Task->expects($this->never())->method('err');

        $this->Task->execute();

        $output = $this->path . DS . 'locale' . DS;
        $result = file_get_contents($output . 'default.pot');
        $this->assertStringContainsString('msgid "Default string"', $result);
        $this->assertStringContainsString('msgid "Other domain string"', $result);
        $this->assertStringContainsString('msgid "Numeric string"', $result);
        $this->assertStringContainsString('msgid "Other domain string"', file_get_contents($output . 'other.pot'));
        $this->assertStringContainsString(
            'msgid "Numeric string"',
            file_get_contents($output . 'LC_NUMERIC' . DS . 'default.pot'),
        );
    }

    /**
     * Test the answers to the prompt about existing .pot files
     *
     * @return void
     */
    public function testWriteFilesExistingFilePrompt(): void
    {
        $source = $this->_writeSource(
            'existing.php',
            "<?php\n__('First string');\n__d('second', 'Second string');\n" .
            "__d('third', 'Third string');\n__d('fourth', 'Fourth string');\n",
        );
        $output = $this->path . DS . 'locale' . DS;
        foreach (['default', 'second', 'third', 'fourth'] as $domain) {
            file_put_contents($output . $domain . '.pot', 'original ' . $domain);
        }

        $this->Task->interactive = false;
        $this->Task->params['paths'] = $source;
        $this->Task->params['output'] = $output;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->expects($this->never())->method('err');

        $prompts = [];
        // default.pot: [N]o, then an empty name and a new name.
        // second.pot: [Y]es. third.pot: [A]ll, so fourth.pot is overwritten without a prompt.
        $responses = ['n', '', 'renamed.pot', 'y', 'a'];
        $this->Task->expects($this->exactly(5))
            ->method('in')
            ->willReturnCallback(function ($prompt) use (&$prompts, &$responses) {
                $prompts[] = $prompt;

                return array_shift($responses);
            });

        $this->Task->execute();

        $this->assertStringContainsString('default.pot already exists in this location.', $prompts[0]);
        $this->assertSame('What would you like to name this file?', $prompts[1]);
        $this->assertSame('What would you like to name this file?', $prompts[2]);
        $this->assertStringContainsString('second.pot already exists in this location.', $prompts[3]);
        $this->assertStringContainsString('third.pot already exists in this location.', $prompts[4]);

        $this->assertSame('original default', file_get_contents($output . 'default.pot'));
        $this->assertStringContainsString('msgid "First string"', file_get_contents($output . 'renamed.pot'));
        $this->assertStringContainsString('msgid "Second string"', file_get_contents($output . 'second.pot'));
        $this->assertStringContainsString('msgid "Third string"', file_get_contents($output . 'third.pot'));
        $this->assertStringContainsString('msgid "Fourth string"', file_get_contents($output . 'fourth.pot'));
    }

    /**
     * Test that an invalid use of a marker function is reported
     *
     * @return void
     */
    public function testExecuteReportsInvalidMarker(): void
    {
        $source = $this->_writeSource(
            'invalid.php',
            "<?php\n__('Valid string');\n__(\$message, (int)\$count);\n" .
            "function __n(\$singular, \$plural, \$count)\n{\n}\n",
        );
        $errors = [];
        $this->Task = $this->_taskRecordingErrors($errors);
        $this->Task->interactive = false;
        $this->Task->params['paths'] = $source;
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';

        $this->Task->execute();

        // The declaration of __n() is not reported, only the call of __() with a variable.
        $this->assertSame(
            "Invalid marker content in {$source}" . DS . "invalid.php:3\n* __(",
            str_replace(realpath($source), $source, $errors[0]),
        );
        $this->assertSame('$message,(int)$count)', implode('', array_slice($errors, 1, -1)));
        $this->assertSame("\n", end($errors));
        $result = file_get_contents($this->path . DS . 'locale' . DS . 'default.pot');
        $this->assertStringContainsString('msgid "Valid string"', $result);
    }

    /**
     * Test that the validation messages go to the domain given by the validation-domain option
     *
     * @return void
     */
    public function testExtractModelValidationWithDomainOption(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS],
        ], App::RESET);
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $this->Task = $this->getMock(
            ExtractTask::class,
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$out, $out, $in],
        );
        $this->Task->expects($this->exactly(2))
            ->method('_isExtractingApp')
            ->willReturn(true);

        $this->Task->params['paths'] = $this->_writeSource('empty.php', "<?php\n");
        $this->Task->params['output'] = $this->path . DS . 'locale';
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['merge'] = 'no';
        $this->Task->params['exclude-plugins'] = true;
        $this->Task->params['validation-domain'] = 'validation_messages';

        $this->Task->execute();

        $output = $this->path . DS . 'locale' . DS;
        $this->assertFileDoesNotExist($output . 'default.pot');
        $result = file_get_contents($output . 'validation_messages.pot');
        $this->assertStringContainsString('Model/PersisterOne.php:validation for field title', $result);
        $this->assertStringContainsString('msgid "Post title is required"', $result);
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Task->getOptionParser();
        $this->assertInstanceOf(ConsoleOptionParser::class, $parser);
        $this->assertStringContainsString('CakePHP Language String Extraction:', $parser->description());

        $options = $parser->options();
        foreach (
            [
                'app', 'paths', 'merge', 'no-location', 'output', 'files', 'exclude-plugins', 'plugin',
                'ignore-model-validation', 'validation-domain', 'exclude', 'overwrite', 'extract-core',
            ] as $name
        ) {
            $this->assertArrayHasKey($name, $options, $name);
        }

        $result = $parser->parse(['--merge', 'no', '--extract-core', 'yes', '--overwrite']);
        $this->assertSame('no', $result[0]['merge']);
        $this->assertSame('yes', $result[0]['extract-core']);
        $this->assertTrue($result[0]['overwrite']);
        $this->assertFalse($result[0]['no-location']);
    }
}
