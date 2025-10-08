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

App::uses('TaskCollection', 'Console');
App::uses('Shell', 'Console');

/**
 * Extended Task
 */
class ExtractAliasedTask extends Shell
{
}

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
        $shell = $this->getMock('Shell', [], [], '', false);
        $dispatcher = $this->getMock('ShellDispatcher', [], [], '', false);
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
    public function testLoad()
    {
        $result = $this->Tasks->load('Extract');
        $this->assertInstanceOf('ExtractTask', $result);
        $this->assertInstanceOf('ExtractTask', $this->Tasks->Extract);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract'], $result, 'loaded() results are wrong.');
    }

    /**
     * test load and enable = false
     *
     * @return void
     */
    public function testLoadWithEnableFalse()
    {
        $result = $this->Tasks->load('Extract', ['enabled' => false]);
        $this->assertInstanceOf('ExtractTask', $result);
        $this->assertInstanceOf('ExtractTask', $this->Tasks->Extract);

        $this->assertFalse($this->Tasks->enabled('Extract'), 'ExtractTask should be disabled');
    }

    /**
     * test missingtask exception
     *
     * @return void
     */
    public function testLoadMissingTask()
    {
        $this->expectException(MissingTaskException::class);
        $this->Tasks->load('ThisTaskShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginTask()
    {
        $dispatcher = $this->getMock('ShellDispatcher', [], [], '', false);
        $shell = $this->getMock('Shell', [], [], '', false);
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Tasks = new TaskCollection($shell, $dispatcher);

        $result = $this->Tasks->load('TestPlugin.OtherTask');
        $this->assertInstanceOf('OtherTaskTask', $result, 'Task class is wrong.');
        $this->assertInstanceOf('OtherTaskTask', $this->Tasks->OtherTask, 'Class is wrong');
        CakePlugin::unload();
    }

    /**
     * test unload()
     *
     * @return void
     */
    public function testUnload()
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
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
    public function testLoadWithAlias()
    {
        $result = $this->Tasks->load('Extract', ['className' => 'ExtractAliased']);
        $this->assertInstanceOf('ExtractAliasedTask', $result);
        $this->assertInstanceOf('ExtractAliasedTask', $this->Tasks->Extract);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract'], $result, 'loaded() results are wrong.');

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestPlugin');

        $result = $this->Tasks->load('SomeTask', ['className' => 'TestPlugin.OtherTask']);
        $this->assertInstanceOf('OtherTaskTask', $result);
        $this->assertInstanceOf('OtherTaskTask', $this->Tasks->SomeTask);

        $result = $this->Tasks->loaded();
        $this->assertEquals(['Extract', 'SomeTask'], $result, 'loaded() results are wrong.');

        CakePlugin::unload();
    }
}
