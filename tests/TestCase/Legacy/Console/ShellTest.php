<?php
/**
 * ShellTest file
 *
 * Test Case for Shell
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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Console;

use Cake\Console\Shell;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Folder;
use RuntimeException;

/**
 * ShellTestShell class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ShellTestShell extends Shell
{
    /**
     * name property
     *
     * @var name
     */
    public $name = 'ShellTestShell';

    /**
     * stopped property
     *
     * @var int
     */
    public $stopped;

    /**
     * testMessage property
     *
     * @var string
     */
    public $testMessage = 'all your base are belong to us';

    /**
     * stop method
     *
     * @param int $status
     * @return void
     */
    protected function _stop($status = 0): void
    {
        $this->stopped = $status;
    }

    protected function _secret(): void
    {
    }

    //@codingStandardsIgnoreStart

    public function do_something(): void
    {
    }

    protected function no_access(): void
    {
    }

    public function log_something(): void
    {
        $this->log($this->testMessage);
    }

    //@codingStandardsIgnoreEnd

    public function mergeVars($properties, $class, $normalize = true): mixed
    {
        return $this->_mergeVars($properties, $class, $normalize);
    }

    public function useLogger($enable = true): void
    {
        $this->_useLogger($enable);
    }
}

/**
 * Class for testing merging vars
 *
 * @package       Cake.Test.Case.Console.Command
 */
class TestMergeShell extends Shell
{
    public $tasks = ['DbConfig', 'Fixture'];

    public $uses = ['Comment'];
}

/**
 * ShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ShellTest extends CakeTestCase
{
    /**
     * Fixtures used in this test case
     *
     * @var array
     */
    public $fixtures = [
        'core.post', 'core.comment', 'core.article', 'core.user',
        'core.tag', 'core.articles_tag', 'core.attachment',
    ];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Console/Command/Task' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Console' . DS . 'Command' . DS . 'Task' . DS],
        ]);

        $output = $this->getMock('ConsoleOutput', [], [], '', false);
        $error = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Shell = new ShellTestShell($output, $error, $in);

        if (is_dir(TMP . 'shell_test')) {
            $Folder = new Folder(TMP . 'shell_test');
            $Folder->delete();
        }
    }

    /**
     * testInitialize method
     *
     * @return void
     */
    public function testInitialize(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ], App::RESET);

        CakePlugin::load('TestLegacyPlugin');
        $this->Shell->tasks = ['DbConfig' => ['one', 'two']];
        $this->Shell->uses = ['TestLegacyPlugin.TestPluginPost'];
        $this->Shell->initialize();

        $this->assertTrue(isset($this->Shell->TestPluginPost));
        $this->assertInstanceOf('TestPluginPost', $this->Shell->TestPluginPost);
        $this->assertEquals('TestPluginPost', $this->Shell->modelClass);
        CakePlugin::unload('TestLegacyPlugin');

        $this->Shell->uses = ['Comment'];
        $this->Shell->initialize();
        $this->assertTrue(isset($this->Shell->Comment));
        $this->assertInstanceOf('Comment', $this->Shell->Comment);
        $this->assertEquals('Comment', $this->Shell->modelClass);

        App::build();
    }

    /**
     * testLoadModel method
     *
     * @return void
     */
    public function testLoadModel(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ], App::RESET);

        $Shell = new TestMergeShell();
        $this->assertEquals('Comment', $Shell->Comment->alias);
        $this->assertInstanceOf('Comment', $Shell->Comment);
        $this->assertEquals('Comment', $Shell->modelClass);

        CakePlugin::load('TestLegacyPlugin');
        $this->Shell->loadModel('TestLegacyPlugin.TestPluginPost');
        $this->assertTrue(isset($this->Shell->TestPluginPost));
        $this->assertInstanceOf('TestPluginPost', $this->Shell->TestPluginPost);
        $this->assertEquals('TestPluginPost', $this->Shell->modelClass);
        CakePlugin::unload('TestLegacyPlugin');

        App::build();
    }

    /**
     * testLoadTasks method
     *
     * @return void
     */
    public function testLoadTasks(): void
    {
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = null;
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = false;
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = true;
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = [];
        $this->assertTrue($this->Shell->loadTasks());

        $this->Shell->tasks = ['TestApple'];
        $this->assertTrue($this->Shell->loadTasks());
        $this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);

        $this->Shell->tasks = 'TestBanana';
        $this->assertTrue($this->Shell->loadTasks());
        $this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);
        $this->assertInstanceOf('TestBananaTask', $this->Shell->TestBanana);

        unset($this->Shell->ShellTestApple, $this->Shell->TestBanana);

        $this->Shell->tasks = ['TestApple', 'TestBanana'];
        $this->assertTrue($this->Shell->loadTasks());
        $this->assertInstanceOf('TestAppleTask', $this->Shell->TestApple);
        $this->assertInstanceOf('TestBananaTask', $this->Shell->TestBanana);
    }

    /**
     * Test getting an instance of a helper
     *
     * @return void
     */
    public function testGetInstanceOfHelper(): void
    {
        $actual = $this->Shell->helper('progress');
        $this->assertInstanceOf('ProgressShellHelper', $actual);
    }

    /**
     * Test getting an invalid helper
     *
     * @return void
     */
    public function testGetInvalidHelper(): void
    {
        $this->expectException(RuntimeException::class);
        $this->Shell->helper('tomato');
    }
}
