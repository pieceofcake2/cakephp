<?php
/**
 * SchemaShellTest Test file
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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use ArrayObject;
use Cake\Console\Command\SchemaShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Model\CakeSchema;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\File;
use Cake\Utility\Folder;
use I18nSchema;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionMethod;
use RuntimeException;
use TestPluginAppSchema;

/**
 * SchemaShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class SchemaShellTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    public $fixtures = [
        'core.article',
        'core.user',
        'core.post',
        'core.auth_user',
        'core.author',
        'core.comment',
        'core.test_plugin_comment',
        'core.aco',
        'core.aro',
        'core.aros_aco',
    ];

    public SchemaShell|MockObject|null $Shell = null;

    /**
     * Temporary directory for the schema and SQL files of a test
     *
     * @var string
     */
    public string $path = '';

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
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop'],
            [$out, $out, $in],
        );
        $this->path = TMP . 'tests' . DS . 'schema_shell_test';
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (!empty($this->file) && $this->file instanceof File) {
            $this->file->delete();
            unset($this->file);
        }
        if (is_dir($this->path . DS . 'readonly')) {
            chmod($this->path . DS . 'readonly', 0755);
        }
        $Folder = new Folder($this->path);
        $Folder->delete();

        parent::tearDown();
    }

    /**
     * test startup method
     *
     * @return void
     */
    public function testStartup(): void
    {
        $this->Shell->startup();
        $this->assertTrue(isset($this->Shell->Schema));
        $this->assertInstanceOf(CakeSchema::class, $this->Shell->Schema);
        $this->assertEquals('App', $this->Shell->Schema->name);
        $this->assertEquals('schema.php', $this->Shell->Schema->file);

        $this->Shell->Schema = null;
        $this->Shell->params = [
            'name' => 'TestSchema',
        ];
        $this->Shell->startup();
        $this->assertEquals('TestSchema', $this->Shell->Schema->name);
        $this->assertEquals('test_schema.php', $this->Shell->Schema->file);
        $this->assertEquals('default', $this->Shell->Schema->connection);
        $this->assertEquals(CONFIG . 'Schema', $this->Shell->Schema->path);

        $this->Shell->Schema = null;
        $this->Shell->params = [
            'file' => 'other_file.php',
            'connection' => 'test',
            'path' => '/test/path',
        ];
        $this->Shell->startup();
        $this->assertEquals('App', $this->Shell->Schema->name);
        $this->assertEquals('other_file.php', $this->Shell->Schema->file);
        $this->assertEquals('test', $this->Shell->Schema->connection);
        $this->assertEquals('/test/path', $this->Shell->Schema->path);
    }

    /**
     * Test View - and that it dumps the schema file to stdout
     *
     * @return void
     */
    public function testView(): void
    {
        $this->Shell->startup();
        $this->Shell->Schema->path = CONFIG . 'Schema';
        $this->Shell->params['file'] = 'i18n.php';
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->expects($this->once())->method('out');
        $this->Shell->view();
    }

    /**
     * test that view() can find plugin schema files.
     *
     * @return void
     */
    public function testViewWithPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->args = ['TestPlugin.schema'];
        $this->Shell->startup();
        $this->Shell->expects($this->exactly(2))->method('_stop');
        $this->Shell->expects($this->atLeastOnce())->method('out');
        $this->Shell->view();

        $this->Shell->args = [];
        $this->Shell->params = ['plugin' => 'TestPlugin'];
        $this->Shell->startup();
        $this->Shell->view();

        App::build();
        CakePlugin::unload();
    }

    /**
     * test dump() with sql file generation
     *
     * @return void
     */
    public function testDumpWithFileWriting(): void
    {
        $this->Shell->params = [
            'name' => 'i18n',
            'connection' => 'test',
            'write' => TMP . 'tests' . DS . 'i18n.sql',
        ];
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();
        $this->Shell->dump();

        $this->file = new File(TMP . 'tests' . DS . 'i18n.sql');
        $contents = $this->file->read();
        $this->assertMatchesRegularExpression('/DROP TABLE/', $contents);
        $this->assertMatchesRegularExpression('/CREATE TABLE.*?i18n/', $contents);
        $this->assertMatchesRegularExpression('/id/', $contents);
        $this->assertMatchesRegularExpression('/model/', $contents);
        $this->assertMatchesRegularExpression('/field/', $contents);
        $this->assertMatchesRegularExpression('/locale/', $contents);
        $this->assertMatchesRegularExpression('/foreign_key/', $contents);
        $this->assertMatchesRegularExpression('/content/', $contents);
    }

    /**
     * test that dump() can find and work with plugin schema files.
     *
     * @return void
     */
    public function testDumpFileWritingWithPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->args = ['TestPlugin.TestPluginApp'];
        $this->Shell->params = [
            'connection' => 'test',
            'write' => TMP . 'tests' . DS . 'dump_test.sql',
        ];
        $this->Shell->startup();
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->dump();

        $this->file = new File(TMP . 'tests' . DS . 'dump_test.sql');
        $contents = $this->file->read();

        $this->assertMatchesRegularExpression('/CREATE TABLE.*?test_plugin_acos/', $contents);
        $this->assertMatchesRegularExpression('/id/', $contents);
        $this->assertMatchesRegularExpression('/model/', $contents);

        $this->file->delete();
        App::build();
        CakePlugin::unload();
    }

    /**
     * test generate with snapshot generation
     *
     * @return void
     */
    public function testGenerateSnapshot(): void
    {
        $this->Shell->path = TMP;
        $this->Shell->params['file'] = 'schema.php';
        $this->Shell->params['force'] = false;
        $this->Shell->args = ['snapshot'];
        $this->Shell->Schema = $this->getMock(CakeSchema::class);

        $this->Shell->Schema->expects($this->once())
            ->method('read')
            ->willReturn(['schema data']);

        $this->Shell->Schema->expects($this->once())
            ->method('write')
            ->with(['schema data', 'file' => 'schema_0.php'])
            ->willReturn('');

        $this->Shell->generate();
    }

    /**
     * test generate without a snapshot.
     *
     * @return void
     */
    public function testGenerateNoOverwrite(): void
    {
        touch(TMP . 'schema.php');
        $this->Shell->params['file'] = 'schema.php';
        $this->Shell->params['force'] = false;
        $this->Shell->args = [];

        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('q'));
        $this->Shell->Schema = $this->getMock(CakeSchema::class);
        $this->Shell->Schema->path = TMP;
        $this->Shell->Schema->expects($this->never())->method('read');

        $this->Shell->generate();
        unlink(TMP . 'schema.php');
    }

    /**
     * test generate with overwriting of the schema files.
     *
     * @return void
     */
    public function testGenerateOverwrite(): void
    {
        touch(TMP . 'schema.php');
        $this->Shell->params['file'] = 'schema.php';
        $this->Shell->params['force'] = false;
        $this->Shell->args = [];

        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('o'));

        $outCalls = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$outCalls) {
                $outCalls[] = $message;

                return 0;
            });

        $this->Shell->Schema = $this->getMock(CakeSchema::class);
        $this->Shell->Schema->path = TMP;

        $this->Shell->Schema
            ->expects($this->once())
            ->method('read')
            ->will($this->returnValue(['schema data']));

        $this->Shell->Schema
            ->expects($this->once())
            ->method('write')
            ->with(['schema data', 'file' => 'schema.php'])
            ->willReturn('ok');

        $this->Shell->generate();

        $this->assertArrayHasKey(1, $outCalls);
        $this->assertMatchesRegularExpression('/Schema file:\s[a-z\.]+\sgenerated/', $outCalls[1]);

        unlink(TMP . 'schema.php');
    }

    /**
     * test that generate() can read plugin dirs and generate schema files for the models
     * in a plugin.
     *
     * @return void
     */
    public function testGenerateWithPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $this->db->cacheSources = false;
        $this->Shell->params = [
            'plugin' => 'TestPlugin',
            'connection' => 'test',
            'force' => false,
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertMatchesRegularExpression('/class TestPluginSchema/', $contents);
        $this->assertMatchesRegularExpression('/public \$posts/', $contents);
        $this->assertMatchesRegularExpression('/public \$auth_users/', $contents);
        $this->assertMatchesRegularExpression('/public \$authors/', $contents);
        $this->assertMatchesRegularExpression('/public \$test_plugin_comments/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$users/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$articles/', $contents);
        CakePlugin::unload();
    }

    /**
     * test generate with specific models
     *
     * @return void
     */
    public function testGenerateModels(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ], App::RESET);
        CakePlugin::load('TestPlugin');

        $this->db->cacheSources = false;
        $this->Shell->params = [
            'plugin' => 'TestPlugin',
            'connection' => 'test',
            'models' => 'TestPluginComment',
            'force' => false,
            'overwrite' => true,
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertMatchesRegularExpression('/class TestPluginSchema/', $contents);
        $this->assertMatchesRegularExpression('/public \$test_plugin_comments/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$authors/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$auth_users/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$posts/', $contents);
        CakePlugin::unload();
    }

    /**
     * test generate with excluded tables
     *
     * @return void
     */
    public function testGenerateExclude(): void
    {
        Configure::write('Acl.database', 'test');
        $this->db->cacheSources = false;
        $this->Shell->params = [
            'connection' => 'test',
            'force' => false,
            'models' => 'Aro, Aco, Permission',
            'overwrite' => true,
            'exclude' => 'acos, aros',
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertStringNotContainsString('public $acos = array(', $contents);
        $this->assertStringNotContainsString('public $aros = array(', $contents);
        $this->assertStringContainsString('public $aros_acos = array(', $contents);
    }

    /**
     * Test schema run create with --yes option
     *
     * @return void
     */
    public function testCreateOptionYes(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );

        $this->Shell->params = [
            'connection' => 'test',
            'yes' => true,
        ];
        $this->Shell->args = ['i18n'];
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->exactly(2))->method('_run');
        $this->Shell->startup();
        $this->Shell->create();
    }

    /**
     * Test schema run create with no table args.
     *
     * @return void
     */
    public function testCreateNoArgs(): void
    {
        $this->Shell->params = [
            'connection' => 'test',
        ];
        $this->Shell->args = ['i18n'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
        $this->Shell->create();

        $db = ConnectionManager::getDataSource('test');

        $db->cacheSources = false;
        $sources = $db->listSources();
        $this->assertTrue(in_array($db->config['prefix'] . 'i18n', $sources));

        $schema = new I18nSchema();
        $db->execute($db->dropSchema($schema));
    }

    /**
     * Test schema run create with no table args.
     *
     * @return void
     */
    public function testCreateWithTableArgs(): void
    {
        $db = ConnectionManager::getDataSource('test');
        $sources = $db->listSources();
        if (in_array('i18n', $sources)) {
            $this->markTestSkipped('i18n table already exists, cannot try to create it again.');
        }
        $this->Shell->params = [
            'connection' => 'test',
            'name' => 'I18n',
            'path' => CONFIG . 'Schema',
        ];
        $this->Shell->args = ['I18n', 'i18n'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
        $this->Shell->create();

        $db = ConnectionManager::getDataSource('test');
        $db->cacheSources = false;
        $sources = $db->listSources();
        $this->assertTrue(in_array($db->config['prefix'] . 'i18n', $sources), 'i18n should be present.');

        $schema = new I18nSchema();
        $db->execute($db->dropSchema($schema, 'i18n'));
    }

    /**
     * test run update with a table arg.
     *
     * @return void
     */
    public function testUpdateWithTable(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );

        $this->Shell->params = [
            'connection' => 'test',
            'force' => true,
            'path' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'Schema',
        ];
        $this->Shell->args = ['SchemaShellTest', 'articles'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())
            ->method('in')
            ->will($this->returnValue('y'));
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with($this->arrayHasKey('articles'), 'update', $this->isInstanceOf(CakeSchema::class));

        $this->Shell->update();
    }

    /**
     * test run update with a table arg. and checks that a CREATE statement is issued
     * table creation
     *
     * @return void
     */
    public function testUpdateWithTableCreate(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );

        $this->Shell->params = [
            'connection' => 'test',
            'force' => true,
            'path' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'Schema',
        ];
        $this->Shell->args = ['SchemaShellTest', 'newone'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())
            ->method('in')
            ->will($this->returnValue('y'));
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with($this->arrayHasKey('newone'), 'update', $this->isInstanceOf(CakeSchema::class));

        $this->Shell->update();
    }

    /**
     * test run update with --yes option
     *
     * @return void
     */
    public function testUpdateWithOptionYes(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );

        $this->Shell->params = [
            'connection' => 'test',
            'force' => true,
            'yes' => true,
            'path' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'Schema',
        ];
        $this->Shell->args = ['SchemaShellTest', 'articles'];
        $this->Shell->startup();
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->once())
            ->method('_run')
            ->with($this->arrayHasKey('articles'), 'update', $this->isInstanceOf(CakeSchema::class));

        $this->Shell->update();
    }

    /**
     * test that the plugin param creates the correct path in the schema object.
     *
     * @return void
     */
    public function testPluginParam(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'plugin' => 'TestPlugin',
            'connection' => 'test',
        ];
        $this->Shell->startup();
        $expected = CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS . 'TestPlugin' . DS . 'config' . DS . 'Schema';
        $this->assertEquals($expected, $this->Shell->Schema->path);
        CakePlugin::unload();
    }

    /**
     * test that underscored names also result in CamelCased class names
     *
     * @return void
     */
    public function testName(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'plugin' => 'TestPlugin',
            'connection' => 'test',
            'name' => 'custom_names',
            'force' => false,
            'overwrite' => true,
        ];
        $this->Shell->startup();
        if (file_exists($this->Shell->Schema->path . DS . 'custom_names.php')) {
            unlink($this->Shell->Schema->path . DS . 'custom_names.php');
        }
        $this->Shell->generate();

        $contents = file_get_contents($this->Shell->Schema->path . DS . 'custom_names.php');
        $this->assertMatchesRegularExpression('/class CustomNamesSchema/', $contents);
        unlink($this->Shell->Schema->path . DS . 'custom_names.php');
        CakePlugin::unload();
    }

    /**
     * test that passing name and file creates the passed filename with the
     * passed class name
     *
     * @return void
     */
    public function testNameAndFile(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'plugin' => 'TestPlugin',
            'connection' => 'test',
            'name' => 'custom_name',
            'file' => 'other_name',
            'force' => false,
            'overwrite' => true,
        ];
        $this->Shell->startup();
        $file = $this->Shell->Schema->path . DS . 'other_name.php';
        if (file_exists($file)) {
            unlink($file);
        }
        $this->Shell->generate();

        $this->assertFileExists($file);
        $contents = file_get_contents($file);
        $this->assertMatchesRegularExpression('/class CustomNameSchema/', $contents);

        if (file_exists($file)) {
            unlink($file);
        }
        CakePlugin::unload();
    }

    /**
     * test that using Plugin.name with write.
     *
     * @return void
     */
    public function testPluginDotSyntaxWithCreate(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $this->Shell->params = [
            'connection' => 'test',
        ];
        $this->Shell->args = ['TestPlugin.TestPluginApp'];
        $this->Shell->startup();
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('y'));
        $this->Shell->create();

        $db = ConnectionManager::getDataSource('test');
        $sources = $db->listSources();
        $this->assertTrue(in_array($db->config['prefix'] . 'test_plugin_acos', $sources));

        $schema = new TestPluginAppSchema();
        $db->execute($db->dropSchema($schema, 'test_plugin_acos'));
        CakePlugin::unload();
    }

    /**
     * Test that view() reports a missing schema file.
     *
     * @return void
     */
    public function testViewMissingFile(): void
    {
        $this->Shell->startup();
        $this->Shell->Schema->path = CONFIG . 'Schema';
        $this->Shell->params['file'] = 'does_not_exist.php';
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->expects($this->once())->method('err')
            ->with($this->stringContains('could not be found'));
        $this->Shell->expects($this->never())->method('out');
        $this->Shell->view();
    }

    /**
     * Loads I18nSchema from the schema file of the application.
     *
     * The tests that read the schema from another path need the class to be loaded already.
     *
     * @return void
     */
    protected function _loadI18nSchema(): void
    {
        if (!class_exists('I18nSchema', false)) {
            require_once CONFIG . 'Schema' . DS . 'i18n.php';
        }
    }

    /**
     * Creates the temporary directory with the given files in it
     *
     * @param array<string> $files The file names
     * @return string The directory
     */
    protected function _createFiles(array $files): string
    {
        new Folder($this->path, true);
        foreach ($files as $file) {
            touch($this->path . DS . $file);
        }

        return $this->path;
    }

    /**
     * Records the messages passed to the given method of the shell mock
     *
     * @param string $method The method name
     * @return ArrayObject<int, mixed> The recorded messages, filled while the test runs
     */
    protected function _record(string $method): ArrayObject
    {
        $messages = new ArrayObject();
        $this->Shell->expects($this->any())
            ->method($method)
            ->willReturnCallback(function ($message = '') use ($messages) {
                $messages[] = $message;

                return 0;
            });

        return $messages;
    }

    /**
     * Calls the protected _run() method of the shell
     *
     * @param array $contents The SQL statements
     * @param CakeSchema $Schema The schema
     * @return mixed
     */
    protected function _callRun(array $contents, CakeSchema $Schema): mixed
    {
        $method = new ReflectionMethod(SchemaShell::class, '_run');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        return $method->invoke($this->Shell, $contents, 'create', $Schema);
    }

    /**
     * Test that generate with --force skips the models and uses the snapshot number given
     *
     * @return void
     */
    public function testGenerateForceWithSnapshotNumber(): void
    {
        $path = $this->_createFiles(['schema.php', 'schema_1.php', 'other.php']);
        $this->Shell->params = ['file' => 'schema.php', 'force' => true, 'snapshot' => 5];
        $this->Shell->args = ['snapshot'];
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->Schema = $this->getMock(CakeSchema::class);
        $this->Shell->Schema->path = $path;

        $this->Shell->Schema->expects($this->once())
            ->method('read')
            ->with(['models' => false])
            ->willReturn(['tables' => []]);
        $this->Shell->Schema->expects($this->once())
            ->method('write')
            ->with(['tables' => [], 'file' => 'schema_5.php'])
            ->willReturn(true);
        $this->Shell->expects($this->once())->method('_stop');

        $this->Shell->generate();
    }

    /**
     * Test that a snapshot number lower than the existing snapshots is not used
     *
     * @return void
     */
    public function testGenerateSnapshotNumberBelowCount(): void
    {
        $path = $this->_createFiles(['schema.php', 'schema_1.php']);
        $this->Shell->params = ['file' => 'schema.php', 'force' => false, 'snapshot' => 1];
        $this->Shell->args = ['snapshot'];
        $this->Shell->Schema = $this->getMock(CakeSchema::class);
        $this->Shell->Schema->path = $path;

        $this->Shell->Schema->expects($this->once())
            ->method('read')
            ->with([])
            ->willReturn(['tables' => []]);
        $this->Shell->Schema->expects($this->once())
            ->method('write')
            ->with(['tables' => [], 'file' => 'schema_2.php'])
            ->willReturn(true);

        $this->Shell->generate();
    }

    /**
     * Test that answering [S]napshot to the prompt writes a snapshot without the excluded tables
     *
     * @return void
     */
    public function testGenerateExistingFileSnapshotWithExclude(): void
    {
        $path = $this->_createFiles(['schema.php']);
        $this->Shell->params = ['file' => 'schema.php', 'force' => false, 'exclude' => 'acos, aros'];
        $this->Shell->args = [];
        $this->Shell->expects($this->once())
            ->method('in')
            ->with($this->stringContains('Schema file exists.'), ['o', 's', 'q'], 's')
            ->willReturn('S');
        $out = $this->_record('out');
        $this->Shell->Schema = $this->getMock(CakeSchema::class);
        $this->Shell->Schema->path = $path;

        $tables = ['acos' => ['id' => []], 'aros' => ['id' => []], 'aros_acos' => ['id' => []]];
        $this->Shell->Schema->expects($this->once())
            ->method('read')
            ->willReturn(['tables' => $tables]);
        $this->Shell->Schema->expects($this->once())
            ->method('write')
            ->with(['tables' => ['aros_acos' => ['id' => []]], 'file' => 'schema_1.php'])
            ->willReturn(true);

        $this->Shell->generate();

        $this->assertContains('Schema file: schema_1.php generated', $out->getArrayCopy());
    }

    /**
     * Test that dump() writes the SQL to stdout and returns it without the write option
     *
     * @return void
     */
    public function testDumpToStdout(): void
    {
        $this->Shell->params = [
            'name' => 'i18n',
            'connection' => 'test',
        ];
        $out = $this->_record('out');
        $this->Shell->expects($this->never())->method('_stop');
        $this->Shell->startup();

        $result = $this->Shell->dump();

        $this->assertMatchesRegularExpression('/DROP TABLE.*?i18n/', $result);
        $this->assertMatchesRegularExpression('/CREATE TABLE.*?i18n/', $result);
        $this->assertSame($result, $out[count($out) - 1]);
    }

    /**
     * Test that dump() names the SQL file after the schema when the write option has no value
     *
     * @return void
     */
    public function testDumpWriteDefaultFileName(): void
    {
        $this->_loadI18nSchema();
        $this->Shell->params = [
            'name' => 'i18n',
            'connection' => 'test',
            'path' => $this->_createFiles([]),
            'write' => true,
        ];
        $out = $this->_record('out');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();

        $this->assertNull($this->Shell->dump());

        $file = $this->path . DS . 'i18n.sql';
        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/CREATE TABLE.*?i18n/', file_get_contents($file));
        $this->assertContains('SQL dump file created in ' . $file, $out->getArrayCopy());
    }

    /**
     * Test that dump() adds the .sql extension to a file name in the schema directory
     *
     * @return void
     */
    public function testDumpWriteFileNameWithoutExtension(): void
    {
        $this->_loadI18nSchema();
        $this->Shell->params = [
            'name' => 'i18n',
            'connection' => 'test',
            'path' => $this->_createFiles([]),
            'write' => 'translations',
        ];
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();

        $this->Shell->dump();

        $this->assertFileExists($this->path . DS . 'translations.sql');
    }

    /**
     * Test that dump() reports a SQL file that could not be written
     *
     * @return void
     */
    public function testDumpWriteFailure(): void
    {
        $readonly = $this->path . DS . 'readonly';
        new Folder($readonly, true);
        chmod($readonly, 0555);
        if (is_writable($readonly)) {
            $this->markTestSkipped('The user running the tests can write into a read-only directory.');
        }
        $this->Shell->params = [
            'name' => 'i18n',
            'connection' => 'test',
            'write' => $readonly . DS . 'dump.sql',
        ];
        $this->Shell->expects($this->once())->method('err')
            ->with('SQL dump could not be created');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();

        $this->assertNull($this->Shell->dump());
        $this->assertFileDoesNotExist($readonly . DS . 'dump.sql');
    }

    /**
     * Test that dump() reports a schema that could not be loaded
     *
     * @return void
     */
    public function testDumpSchemaNotLoaded(): void
    {
        $this->Shell->params = [
            'name' => 'SchemaShellMissing',
            'connection' => 'test',
            'path' => $this->_createFiles([]),
        ];
        $this->Shell->expects($this->once())->method('err')
            ->with('Schema could not be loaded');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();

        $this->assertNull($this->Shell->dump());
    }

    /**
     * Test that create with --dry outputs the statements instead of running them
     *
     * @return void
     */
    public function testCreateDryRun(): void
    {
        $this->Shell->params = [
            'connection' => 'test',
            'dry' => true,
            'yes' => true,
        ];
        $this->Shell->args = ['i18n'];
        $this->Shell->expects($this->never())->method('in');
        $out = $this->_record('out');
        $this->Shell->startup();

        $this->Shell->create();

        $out = $out->getArrayCopy();
        $this->assertContains('Performing a dry run.', $out);
        $this->assertContains('Dry run for i18n :', $out);
        $this->assertContains('End create.', $out);
        $this->assertNotContains('i18n updated.', $out);

        $db = ConnectionManager::getDataSource('test');
        $db->cacheSources = false;
        $this->assertNotContains($db->config['prefix'] . 'i18n', $db->listSources());
    }

    /**
     * Test that create with --snapshot loads the numbered schema file
     *
     * @return void
     */
    public function testCreateSnapshot(): void
    {
        $this->_loadI18nSchema();
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );
        $this->Shell->params = [
            'connection' => 'test',
            'snapshot' => 3,
            'yes' => true,
        ];
        $this->Shell->args = ['i18n'];
        $schemas = [];
        $this->Shell->expects($this->exactly(2))
            ->method('_run')
            ->willReturnCallback(function ($contents, $event, $Schema) use (&$schemas) {
                $schemas[$event] = $Schema;

                return null;
            });
        $this->Shell->startup();

        $this->Shell->create();

        $this->assertSame(['drop', 'create'], array_keys($schemas));
        $this->assertSame('i18n_3.php', $schemas['create']->file);
    }

    /**
     * Test that create reports a schema that could not be loaded
     *
     * @return void
     */
    public function testCreateSchemaNotLoaded(): void
    {
        $this->Shell->params = [
            'name' => 'SchemaShellMissing',
            'connection' => 'test',
            'path' => $this->_createFiles([]),
        ];
        $err = $this->_record('err');
        $this->Shell->expects($this->once())->method('_stop')
            ->with(2)
            ->willThrowException(new RuntimeException('stopped'));
        $this->Shell->startup();

        try {
            $this->Shell->create();
            $this->fail('The shell should have stopped.');
        } catch (RuntimeException $e) {
            $this->assertSame('stopped', $e->getMessage());
        }

        $this->assertSame(
            [
                '<error>Error</error>: The chosen schema could not be loaded. Attempted to load:',
                '- file: ' . $this->path . DS . 'schema_shell_missing.php',
                '- name: SchemaShellMissing',
            ],
            $err->getArrayCopy(),
        );
    }

    /**
     * Test that create with a table that is not in the schema does nothing
     *
     * @return void
     */
    public function testCreateUnknownTable(): void
    {
        $this->Shell->params = ['connection' => 'test'];
        $this->Shell->args = ['i18n', 'not_in_schema'];
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->startup();
        $out = $this->_record('out');

        $this->Shell->create();

        $this->assertSame(['Schema is up to date.'], $out->getArrayCopy());
    }

    /**
     * Test that update without a table compares all tables of the schema
     *
     * @return void
     */
    public function testUpdateAllTables(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );
        $this->Shell->params = [
            'connection' => 'test',
            'force' => true,
            'path' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'Schema',
        ];
        $this->Shell->args = ['SchemaShellTest'];
        $this->Shell->startup();
        $this->Shell->expects($this->once())
            ->method('in')
            ->with('Are you sure you want to alter the tables?', ['y', 'n'], 'n')
            ->willReturn('y');
        $contents = [];
        $this->Shell->expects($this->once())
            ->method('_run')
            ->willReturnCallback(function ($sql) use (&$contents) {
                $contents = $sql;

                return null;
            });

        $this->Shell->update();

        $this->assertArrayHasKey('articles', $contents);
        $this->assertArrayHasKey('newone', $contents);
        $this->assertMatchesRegularExpression('/CREATE TABLE.*?newone/', $contents['newone']);
    }

    /**
     * Test that update does not alter the tables when the answer is no
     *
     * @return void
     */
    public function testUpdateAnswerNo(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );
        $this->Shell->params = [
            'connection' => 'test',
            'force' => true,
            'path' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'Schema',
        ];
        $this->Shell->args = ['SchemaShellTest', 'articles'];
        $this->Shell->startup();
        $this->Shell->expects($this->once())->method('in')->willReturn('n');
        $this->Shell->expects($this->never())->method('_run');

        $this->Shell->update();
    }

    /**
     * Test that update with a table that has no changes does nothing
     *
     * @return void
     */
    public function testUpdateUpToDate(): void
    {
        $this->Shell = $this->getMock(
            SchemaShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop', '_run'],
            [&$this->Dispatcher],
        );
        $this->Shell->params = [
            'connection' => 'test',
            'force' => true,
            'path' => CORE_TESTS . DS . 'test_app' . DS . 'config' . DS . 'Schema',
        ];
        $this->Shell->args = ['SchemaShellTest', 'not_in_schema'];
        $this->Shell->startup();
        $out = $this->_record('out');
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->never())->method('_run');
        $this->Shell->expects($this->once())->method('_stop');

        $this->Shell->update();

        $this->assertSame(['Comparing Database to Schema...', 'Schema is up to date.'], $out->getArrayCopy());
    }

    /**
     * Test that _run() reports empty contents
     *
     * @return void
     */
    public function testRunWithoutContents(): void
    {
        $this->Shell->params = ['connection' => 'test'];
        $this->Shell->startup();
        $this->Shell->expects($this->once())->method('err')
            ->with('Sql could not be run');

        $this->assertNull($this->_callRun([], new CakeSchema()));
    }

    /**
     * Test that _run() skips empty statements and stops when the before callback fails
     *
     * @return void
     */
    public function testRunEmptyStatementAndFailingBefore(): void
    {
        $this->Shell->params = ['connection' => 'test'];
        $this->Shell->startup();
        $out = $this->_record('out');
        $Schema = $this->getMock(CakeSchema::class, ['before', 'after']);
        $Schema->expects($this->once())
            ->method('before')
            ->with(['create' => 'second'])
            ->willReturn(false);
        $Schema->expects($this->never())->method('after');

        $result = $this->_callRun(['first' => '', 'second' => 'SELECT 1', 'third' => 'SELECT 2'], $Schema);

        $this->assertFalse($result);
        $this->assertSame(['first is up to date.'], $out->getArrayCopy());
    }

    /**
     * Test that _run() reports a statement that fails and continues with the next one
     *
     * @return void
     */
    public function testRunFailingStatement(): void
    {
        $this->Shell->params = ['connection' => 'test'];
        $this->Shell->startup();
        $out = $this->_record('out');
        $err = $this->_record('err');
        $events = [];
        $Schema = $this->getMock(CakeSchema::class, ['after']);
        $Schema->expects($this->exactly(2))
            ->method('after')
            ->willReturnCallback(function ($event) use (&$events) {
                $events[] = $event;
            });

        $this->assertNull($this->_callRun(['broken' => 'THIS IS NOT SQL', 'working' => 'SELECT 1'], $Schema));

        $this->assertCount(1, $err);
        $this->assertStringStartsWith('broken: ', $err[0]);
        $this->assertSame(['working updated.'], $out->getArrayCopy());
        $this->assertSame('broken', $events[0]['create']);
        $this->assertSame($err[0], $events[0]['errors']);
        $this->assertSame(['create' => 'working', 'errors' => null], $events[1]);
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

        $subcommands = $parser->subcommands();
        $this->assertSame(['view', 'generate', 'dump', 'create', 'update'], array_keys($subcommands));

        $generate = $subcommands['generate']->parser();
        $this->assertSame(
            ['help', 'verbose', 'quiet', 'plugin', 'path', 'file', 'name', 'connection', 'snapshot', 'force', 'models', 'exclude'],
            array_keys($generate->options()),
        );
        $result = $generate->parse(['-f', '-s', '2', '--exclude', 'acos']);
        $this->assertTrue($result[0]['force']);
        $this->assertSame('2', $result[0]['snapshot']);
        $this->assertSame('acos', $result[0]['exclude']);
        $this->assertSame('default', $result[0]['connection']);
        $this->assertSame(CONFIG . 'Schema', $result[0]['path']);

        $this->assertArrayHasKey('write', $subcommands['dump']->parser()->options());
        $this->assertArrayHasKey('dry', $subcommands['create']->parser()->options());
        $this->assertArrayHasKey('yes', $subcommands['update']->parser()->options());
    }
}
