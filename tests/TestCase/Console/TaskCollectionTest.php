<?php

/**
 * TaskCollectionTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Console
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console;

use Cake\Console\Command\Task\CommandTask;
use Cake\Console\Command\Task\ExtractTask;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\Console\Shell;
use Cake\Console\ShellDispatcher;
use Cake\Console\TaskCollection;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingTaskException;
use Cake\TestSuite\CakeTestCase;
use TestApp\Console\Command\Task\NamespacedTask;
use TestPlugin\Console\Command\Task\OtherTaskTask;

/**
 * Extended Task
 */
class ExtractAliasedTask extends Shell
{
}
class_alias(ExtractAliasedTask::class, 'App\\Console\\Command\\Task\\ExtractAliasedTask');

/**
 * TaskCollectionTest
 *
 * @package       Cake.Test.Case.Console
 */
class TaskCollectionTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $shell = $this->getMock(Shell::class, [], [], '', false);
        $dispatcher = $this->getMock(ShellDispatcher::class, [], [], '', false);
        $this->Tasks = new TaskCollection($shell, $dispatcher);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Tasks);

        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded tasks
     *
     * @return void
     */
    public function testLoad(): void
    {
        $result = $this->Tasks->load('Extract');
        $this->assertInstanceOf(ExtractTask::class, $result);
        $this->assertInstanceOf(ExtractTask::class, $this->Tasks->Extract);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract'], $result, 'loaded() results are wrong.');
    }

    /**
     * test load and enable = false
     *
     * @return void
     */
    public function testLoadWithEnableFalse(): void
    {
        $result = $this->Tasks->load('Extract', ['enabled' => false]);
        $this->assertInstanceOf(ExtractTask::class, $result);
        $this->assertInstanceOf(ExtractTask::class, $this->Tasks->Extract);

        $this->assertFalse($this->Tasks->enabled('Extract'), 'ExtractTask should be disabled');
    }

    /**
     * test missingtask exception
     *
     * @return void
     */
    public function testLoadMissingTask(): void
    {
        $this->expectException(MissingTaskException::class);
        $this->Tasks->load('ThisTaskShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginTask(): void
    {
        $dispatcher = $this->getMock(ShellDispatcher::class, [], [], '', false);
        $shell = $this->getMock(Shell::class, [], [], '', false);
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Tasks = new TaskCollection($shell, $dispatcher);

        $result = $this->Tasks->load('TestPlugin.OtherTask');
        $this->assertInstanceOf(OtherTaskTask::class, $result, 'Task class is wrong.');
        $this->assertInstanceOf(OtherTaskTask::class, $this->Tasks->OtherTask, 'Class is wrong');
        CakePlugin::unload();
    }

    /**
     * test unload()
     *
     * @return void
     */
    public function testUnload(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');

        $this->Tasks->load('Extract');
        $this->Tasks->load('TestPlugin.OtherTask');

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract', 'OtherTask'], $result, 'loaded tasks is wrong');

        $this->Tasks->unload('OtherTask');
        $this->assertFalse(isset($this->Tasks->OtherTask));
        $this->assertTrue(isset($this->Tasks->Extract));

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract'], $result, 'loaded tasks is wrong');

        CakePlugin::unload();
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Tasks->load('Extract', ['className' => 'ExtractAliased']);
        $this->assertInstanceOf(ExtractAliasedTask::class, $result);
        $this->assertInstanceOf(ExtractAliasedTask::class, $this->Tasks->Extract);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract'], $result, 'loaded() results are wrong.');

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');

        $result = $this->Tasks->load('SomeTask', ['className' => 'TestPlugin.OtherTask']);
        $this->assertInstanceOf(OtherTaskTask::class, $result);
        $this->assertInstanceOf(OtherTaskTask::class, $this->Tasks->SomeTask);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract', 'SomeTask'], $result, 'loaded() results are wrong.');

        CakePlugin::unload();
    }

    /**
     * Test loading tasks by fully qualified class name and from App.namespace.
     *
     * @return void
     */
    public function testLoadNamespaced(): void
    {
        $result = $this->Tasks->load('Namespaced', ['className' => NamespacedTask::class]);
        $this->assertInstanceOf(NamespacedTask::class, $result);
        $this->assertSame('Namespaced', $result->name);

        $result = $this->Tasks->load('WithoutSuffix', ['className' => 'TestApp\Console\Command\Task\Namespaced']);
        $this->assertInstanceOf(NamespacedTask::class, $result);

        $this->assertInstanceOf(CommandTask::class, $this->Tasks->load('Command'));

        Configure::write('App.namespace', 'TestApp');
        $shell = $this->getMock(Shell::class, [], [], '', false);
        $Tasks = new TaskCollection($shell);
        $this->assertInstanceOf(NamespacedTask::class, $Tasks->load('Namespaced'));
    }

    /**
     * Test that a fully qualified class name as the name or as key of $tasks gives
     * the short name as alias like the short name with className.
     *
     * @return void
     */
    public function testLoadNamespacedClassName(): void
    {
        $this->Tasks->load('Namespaced', ['className' => NamespacedTask::class]);

        foreach ([NamespacedTask::class, 'TestApp\Console\Command\Task\Namespaced'] as $name) {
            $Tasks = new TaskCollection($this->getMock(Shell::class, [], [], '', false));
            $result = $Tasks->load($name);
            $this->assertInstanceOf(NamespacedTask::class, $result);
            $this->assertSame($result, $Tasks->Namespaced);
            $this->assertSame($this->Tasks->loaded(), $Tasks->loaded());
            $this->assertSame($result, $Tasks->load($name));
        }

        $cases = [
            ['Namespaced' => ['className' => NamespacedTask::class]],
            [NamespacedTask::class],
            ['TestApp\Console\Command\Task\Namespaced'],
        ];
        foreach ($cases as $tasks) {
            $Shell = new Shell($this->getMock(ConsoleOutput::class), $this->getMock(ConsoleOutput::class), $this->getMock(ConsoleInput::class));
            $Shell->tasks = $tasks;
            $Shell->loadTasks();
            $this->assertSame(['Namespaced'], $Shell->taskNames);
            $this->assertTrue($Shell->hasTask('namespaced'));
            $this->assertInstanceOf(NamespacedTask::class, $Shell->Namespaced);
            $this->assertSame(['Namespaced'], $Shell->Tasks->loaded());
        }
    }

    /**
     * Test that the missing class of a fully qualified class name has the suffix once.
     *
     * @return void
     */
    public function testLoadMissingNamespacedClassName(): void
    {
        $cases = [
            'NoSuch' => 'NoSuchTask',
            'TestApp\Console\Command\Task\Missing' => 'TestApp\Console\Command\Task\MissingTask',
            'TestApp\Console\Command\Task\MissingTask' => 'TestApp\Console\Command\Task\MissingTask',
            '\TestApp\Console\Command\Task\MissingTask' => 'TestApp\Console\Command\Task\MissingTask',
        ];
        foreach ($cases as $name => $expected) {
            $Collection = new TaskCollection($this->getMock(Shell::class, [], [], '', false));
            try {
                $Collection->load($name);
                $this->fail('No exception for ' . $name);
            } catch (MissingTaskException $e) {
                $this->assertSame($expected, $e->getAttributes()['class']);
            }
        }
    }
}
