<?php
/**
 * PluginTask Test file
 *
 * Test Case for plugin generation shell task
 *
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command.Task
 * @since         CakePHP v 1.3.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('ShellDispatcher', 'Console');
App::uses('ConsoleOutput', 'Console');
App::uses('ConsoleInput', 'Console');
App::uses('Shell', 'Console');
App::uses('PluginTask', 'Console/Command/Task');
App::uses('ModelTask', 'Console/Command/Task');
App::uses('Folder', 'Utility');
App::uses('File', 'Utility');

/**
 * PluginTaskPlugin class
 *
 * @package       Cake.Test.Case.Console.Command.Task
 */
class PluginTaskTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->out = $this->getMock('ConsoleOutput', [], [], '', false);
        $this->in = $this->getMock('ConsoleInput', [], [], '', false);

        $this->Task = $this->getMock(
            'PluginTask',
            ['in', 'err', 'createFile', '_stop', 'clear'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task->path = TMP . 'tests' . DS;
        $this->Task->bootstrap = TMP . 'tests' . DS . 'bootstrap.php';
        touch($this->Task->bootstrap);

        $this->_paths = $paths = App::path('plugins');
        foreach ($paths as $i => $p) {
            if (!is_dir($p)) {
                array_splice($paths, $i, 1);
            }
        }
        $this->_testPath = array_push($paths, TMP . 'tests' . DS) - 1;
        App::build(['plugins' => $paths]);
    }

    /**
     * tearDown()
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (file_exists($this->Task->bootstrap)) {
            unlink($this->Task->bootstrap);
        }

        parent::tearDown();
    }

    /**
     * test bake()
     *
     * @return void
     */
    public function testBakeFoldersAndFiles()
    {
        $this->Task->expects($this->exactly(2))
            ->method('in')
            ->willReturnOnConsecutiveCalls($this->_testPath, 'y');

        $path = $this->Task->path . 'BakeTestPlugin';

        $createFileCalls = [];
        $this->Task->expects($this->exactly(2))
            ->method('createFile')
            ->willReturnCallback(function ($file, $content) use (&$createFileCalls) {
                $createFileCalls[] = ['file' => $file, 'content' => $content];
            });

        $this->Task->bake('BakeTestPlugin');

        $file1 = $path . DS . 'Controller' . DS . 'BakeTestPluginAppController.php';
        $this->assertEquals($file1, $createFileCalls[0]['file']);

        $file2 = $path . DS . 'Model' . DS . 'BakeTestPluginAppModel.php';
        $this->assertEquals($file2, $createFileCalls[1]['file']);

        $path = $this->Task->path . 'BakeTestPlugin';
        $this->assertTrue(is_dir($path), 'No plugin dir %s');

        $directories = [
            'Config' . DS . 'Schema',
            'Console' . DS . 'Command' . DS . 'Task',
            'Console' . DS . 'Templates',
            'Controller' . DS . 'Component',
            'Lib',
            'Locale' . DS . 'eng' . DS . 'LC_MESSAGES',
            'Model' . DS . 'Behavior',
            'Model' . DS . 'Datasource',
            'Test' . DS . 'Case' . DS . 'Controller' . DS . 'Component',
            'Test' . DS . 'Case' . DS . 'Lib',
            'Test' . DS . 'Case' . DS . 'Model' . DS . 'Behavior',
            'Test' . DS . 'Case' . DS . 'Model' . DS . 'Datasource',
            'Test' . DS . 'Case' . DS . 'View' . DS . 'Helper',
            'Test' . DS . 'Fixture',
            'View' . DS . 'Elements',
            'View' . DS . 'Helper',
            'View' . DS . 'Layouts',
            'webroot' . DS . 'css',
            'webroot' . DS . 'js',
            'webroot' . DS . 'img',
        ];
        foreach ($directories as $dir) {
            $this->assertTrue(is_dir($path . DS . $dir), 'Missing directory for ' . $dir);
        }

        $Folder = new Folder($this->Task->path . 'BakeTestPlugin');
        $Folder->delete();
    }

    /**
     * test execute with no args, flowing into interactive,
     *
     * @return void
     */
    public function testExecuteWithNoArgs()
    {
        $this->Task->expects($this->exactly(3))
            ->method('in')
            ->willReturnOnConsecutiveCalls('TestPlugin', $this->_testPath, 'y');

        $path = $this->Task->path . 'TestPlugin';

        $createFileCalls = [];
        $this->Task->expects($this->exactly(2))
            ->method('createFile')
            ->willReturnCallback(function ($file, $content) use (&$createFileCalls) {
                $createFileCalls[] = ['file' => $file, 'content' => $content];
            });

        $this->Task->args = [];
        $this->Task->execute();

        $file1 = $path . DS . 'Controller' . DS . 'TestPluginAppController.php';
        $this->assertEquals($file1, $createFileCalls[0]['file']);

        $file2 = $path . DS . 'Model' . DS . 'TestPluginAppModel.php';
        $this->assertEquals($file2, $createFileCalls[1]['file']);

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * Test Execute
     *
     * @return void
     */
    public function testExecuteWithOneArg()
    {
        $this->Task->expects($this->exactly(2))
            ->method('in')
            ->willReturnOnConsecutiveCalls($this->_testPath, 'y');

        $path = $this->Task->path . 'BakeTestPlugin';

        $createFileCalls = [];
        $this->Task->expects($this->exactly(2))
            ->method('createFile')
            ->willReturnCallback(function ($file, $content) use (&$createFileCalls) {
                $createFileCalls[] = ['file' => $file, 'content' => $content];
            });

        $this->Task->args = ['BakeTestPlugin'];
        $this->Task->execute();

        $file1 = $path . DS . 'Controller' . DS . 'BakeTestPluginAppController.php';
        $this->assertEquals($file1, $createFileCalls[0]['file']);

        $file2 = $path . DS . 'Model' . DS . 'BakeTestPluginAppModel.php';
        $this->assertEquals($file2, $createFileCalls[1]['file']);

        $Folder = new Folder($this->Task->path . 'BakeTestPlugin');
        $Folder->delete();
    }

    /**
     * Test that findPath ignores paths that don't exist.
     *
     * @return void
     */
    public function testFindPathNonExistant()
    {
        $paths = App::path('plugins');
        $last = count($paths);

        array_unshift($paths, '/fake/path');
        $paths[] = '/fake/path2';

        $this->Task = $this->getMock(
            'PluginTask',
            ['in', 'out', 'err', 'createFile', '_stop'],
            [$this->out, $this->out, $this->in],
        );
        $this->Task->path = TMP . 'tests' . DS;

        // Make sure the added path is filtered out.
        $this->Task->expects($this->exactly($last))
            ->method('out');

        $this->Task->expects($this->once())
            ->method('in')
            ->will($this->returnValue($last));

        $this->Task->findPath($paths);
    }
}
