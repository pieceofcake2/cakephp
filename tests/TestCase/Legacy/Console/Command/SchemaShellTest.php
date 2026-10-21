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

namespace Cake\Test\TestCase\Legacy\Console\Command;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\File;
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
     * @var array
     */
    public $fixtures = [
        'core.article', 'core.user', 'core.post', 'core.auth_user', 'core.author',
        'core.comment', 'core.test_plugin_comment', 'core.aco', 'core.aro', 'core.aros_aco',
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
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $out = $this->getMock('ConsoleOutput', [], [], '', false);
        $in = $this->getMock('ConsoleInput', [], [], '', false);
        $this->Shell = $this->getMock(
            'SchemaShell',
            ['in', 'out', 'hr', 'createFile', 'error', 'err', '_stop'],
            [$out, $out, $in],
        );
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

        parent::tearDown();
    }

    /**
     * test that view() can find plugin schema files.
     *
     * @return void
     */
    public function testViewWithPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->Shell->args = ['TestLegacyPlugin.schema'];
        $this->Shell->startup();
        $this->Shell->expects($this->exactly(2))->method('_stop');
        $this->Shell->expects($this->atLeastOnce())->method('out');
        $this->Shell->view();

        $this->Shell->args = [];
        $this->Shell->params = ['plugin' => 'TestLegacyPlugin'];
        $this->Shell->startup();
        $this->Shell->view();

        App::build();
        CakePlugin::unload();
    }

    /**
     * test that dump() can find and work with plugin schema files.
     *
     * @return void
     */
    public function testDumpFileWritingWithPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->Shell->args = ['TestLegacyPlugin.TestPluginApp'];
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
     * test that generate() can read plugin dirs and generate schema files for the models
     * in a plugin.
     *
     * @return void
     */
    public function testGenerateWithPlugins(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $this->db->cacheSources = false;
        $this->Shell->params = [
            'plugin' => 'TestLegacyPlugin',
            'connection' => 'test',
            'force' => false,
        ];
        $this->Shell->startup();
        $this->Shell->Schema->path = TMP . 'tests' . DS;

        $this->Shell->generate();
        $this->file = new File(TMP . 'tests' . DS . 'schema.php');
        $contents = $this->file->read();

        $this->assertMatchesRegularExpression('/class TestLegacyPluginSchema/', $contents);
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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');

        $this->db->cacheSources = false;
        $this->Shell->params = [
            'plugin' => 'TestLegacyPlugin',
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

        $this->assertMatchesRegularExpression('/class TestLegacyPluginSchema/', $contents);
        $this->assertMatchesRegularExpression('/public \$test_plugin_comments/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$authors/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$auth_users/', $contents);
        $this->assertDoesNotMatchRegularExpression('/public \$posts/', $contents);
        CakePlugin::unload();
    }

    /**
     * test that the plugin param creates the correct path in the schema object.
     *
     * @return void
     */
    public function testPluginParam(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->Shell->params = [
            'plugin' => 'TestLegacyPlugin',
            'connection' => 'test',
        ];
        $this->Shell->startup();
        $expected = CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS . 'TestLegacyPlugin' . DS . 'Config' . DS . 'Schema';
        // Config/ is also found as config/ on case-insensitive file systems
        $this->assertEqualsIgnoringCase($expected, $this->Shell->Schema->path);
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
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->Shell->params = [
            'connection' => 'test',
        ];
        $this->Shell->args = ['TestLegacyPlugin.TestPluginApp'];
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
}
