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

namespace Cake\Test\TestCase\Legacy\Console\Command\Task;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Folder;

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
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Task = $this->getMock(
            'ExtractTask',
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
     * Tests that it is possible to exclude plugin paths by enabling the param option for the ExtractTask
     *
     * @return void
     */
    public function testExtractExcludePlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        $this->out = $this->getMock('ConsoleOutput', [], [], '', false);
        $this->in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Task = $this->getMock(
            'ExtractTask',
            ['_isExtractingApp', '_extractValidationMessages', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_legacy_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['exclude-plugins'] = true;

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'default.pot');
        $this->assertDoesNotMatchRegularExpression('#TestLegacyPlugin#', $result);
    }

    /**
     * Test that is possible to extract messages form a single plugin
     *
     * @return void
     */
    public function testExtractPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);

        $this->out = $this->getMock('ConsoleOutput', [], [], '', false);
        $this->in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Task = $this->getMock(
            'ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['plugin'] = 'TestLegacyPlugin';

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
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        $this->out = $this->getMock('ConsoleOutput', [], [], '', false);
        $this->in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Task = $this->getMock(
            'ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_legacy_app' . DS;
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
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'Model' . DS],
        ]);
        $this->out = $this->getMock('ConsoleOutput', [], [], '', false);
        $this->in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Task = $this->getMock(
            'ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task->expects($this->exactly(2))->method('_isExtractingApp')->will($this->returnValue(true));

        $this->Task->params['paths'] = CORE_TESTS . DS . 'test_legacy_app' . DS;
        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['extract-core'] = 'no';
        $this->Task->params['exclude-plugins'] = true;
        $this->Task->params['ignore-model-validation'] = false;

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'test_legacy_plugin.pot');
        $this->assertStringContainsString('Plugin/TestLegacyPlugin/Model/TestPluginPost.php:validation for field title', $result);
        $this->assertStringContainsString('Plugin/TestLegacyPlugin/Model/TestPluginPost.php:validation for field body', $result);
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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        $this->out = $this->getMock('ConsoleOutput', [], [], '', false);
        $this->in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Task = $this->getMock(
            'ExtractTask',
            ['_isExtractingApp', 'in', 'out', 'err', 'clear', '_stop'],
            [$this->out, $this->out, $this->in],
        );

        $this->Task->params['output'] = $this->path . DS;
        $this->Task->params['ignore-model-validation'] = false;
        $this->Task->params['plugin'] = 'TestLegacyPlugin';

        $this->Task->execute();
        $result = file_get_contents($this->path . DS . 'test_legacy_plugin.pot');
        $this->assertStringContainsString('Model/TestPluginPost.php:validation for field title', $result);
        $this->assertStringContainsString('Model/TestPluginPost.php:validation for field body', $result);
        $this->assertStringContainsString('msgid "Post title is required"', $result);
        $this->assertStringContainsString('msgid "Post body is required"', $result);
        $this->assertStringContainsString('msgid "Post body is super required"', $result);
        $this->assertStringNotContainsString('Plugin/TestLegacyPlugin/Model/TestPluginPost.php:validation for field title', $result);
    }
}
