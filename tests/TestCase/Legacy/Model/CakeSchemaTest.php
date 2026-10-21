<?php
/**
 * Test for Schema database management
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.5550
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingTableException;
use Cake\Model\CakeSchema;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;

/**
 * TestAppSchema class
 *
 * @package       Cake.Test.Case.Model
 */
class TestAppSchema extends CakeSchema
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'MyApp';

    /**
     * comments property
     *
     * @var array
     */
    public $comments = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'article_id' => ['type' => 'integer', 'null' => false],
        'user_id' => ['type' => 'integer', 'null' => false],
        'comment' => ['type' => 'text', 'null' => true, 'default' => null],
        'published' => ['type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
        'tableParameters' => [],
    ];

    /**
     * posts property
     *
     * @var array
     */
    public $posts = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'author_id' => ['type' => 'integer', 'null' => false],
        'title' => ['type' => 'string', 'null' => false],
        'body' => ['type' => 'text', 'null' => true, 'default' => null],
        'published' => ['type' => 'string', 'null' => true, 'default' => 'N', 'length' => 1],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
        'tableParameters' => [],
    ];

    /**
     * posts_tags property
     *
     * @var array
     */
    public $posts_tags = [
        'post_id' => ['type' => 'integer', 'null' => false, 'key' => 'primary'],
        'tag_id' => ['type' => 'string', 'null' => false, 'key' => 'primary'],
        'indexes' => ['posts_tag' => ['column' => ['tag_id', 'post_id'], 'unique' => 1]],
        'tableParameters' => [],
    ];

    /**
     * tags property
     *
     * @var array
     */
    public $tags = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'tag' => ['type' => 'string', 'null' => false],
        'created' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'updated' => ['type' => 'datetime', 'null' => true, 'default' => null],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
        'tableParameters' => [],
    ];

    /**
     * datatypes property
     *
     * @var array
     */
    public $datatypes = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => 0, 'key' => 'primary'],
        'float_field' => ['type' => 'float', 'null' => false, 'length' => '5,2', 'default' => ''],
        'decimal_field' => ['type' => 'decimal', 'length' => '6,3', 'default' => '0.000'],
        'huge_int' => ['type' => 'biginteger'],
        'normal_int' => ['type' => 'integer'],
        'small_int' => ['type' => 'smallinteger'],
        'tiny_int' => ['type' => 'tinyinteger'],
        'bool' => ['type' => 'boolean', 'null' => false, 'default' => false],
        'indexes' => ['PRIMARY' => ['column' => 'id', 'unique' => true]],
        'tableParameters' => [],
    ];

    /**
     * setup method
     *
     * @param mixed $version
     * @return void
     */
    public function setup($version): void
    {
    }

    /**
     * teardown method
     *
     * @param mixed $version
     * @return void
     */
    public function teardown($version): void
    {
    }
}

/**
 * CakeSchemaTest
 *
 * @package       Cake.Test.Case.Model
 */
class CakeSchemaTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.post', 'core.tag', 'core.posts_tag', 'core.test_plugin_comment',
        'core.datatype', 'core.auth_user', 'core.author',
        'core.test_plugin_article', 'core.user', 'core.comment',
        'core.prefix_test',
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
        ConnectionManager::getDataSource('test')->cacheSources = false;
        $this->Schema = new TestAppSchema();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        if (file_exists(TMP . 'tests' . DS . 'schema.php')) {
            unlink(TMP . 'tests' . DS . 'schema.php');
        }
        unset($this->Schema);
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * testSchemaRead method
     *
     * @return void
     */
    public function testSchemaRead(): void
    {
        $read = $this->Schema->read([
            'connection' => 'test',
            'name' => 'TestApp',
            'models' => ['SchemaPost', 'SchemaComment', 'SchemaTag', 'SchemaDatatype'],
        ]);
        unset($read['tables']['missing']);

        $expected = ['comments', 'datatypes', 'posts', 'posts_tags', 'tags'];
        foreach ($expected as $table) {
            $this->assertTrue(isset($read['tables'][$table]), 'Missing table ' . $table);
        }
        foreach ($this->Schema->tables as $table => $fields) {
            $this->assertEquals(array_keys($fields), array_keys($read['tables'][$table]));
        }

        if (isset($read['tables']['datatypes']['float_field']['length'])) {
            $this->assertEquals(
                $this->Schema->tables['datatypes']['float_field']['length'],
                $read['tables']['datatypes']['float_field']['length'],
            );
        }

        $this->assertEquals(
            $this->Schema->tables['datatypes']['float_field']['type'],
            $read['tables']['datatypes']['float_field']['type'],
        );

        $this->assertEquals(
            $this->Schema->tables['datatypes']['float_field']['null'],
            $read['tables']['datatypes']['float_field']['null'],
        );

        $db = ConnectionManager::getDataSource('test');
        $config = $db->config;
        $config['prefix'] = 'schema_test_prefix_';
        ConnectionManager::create('schema_prefix', $config);
        $read = $this->Schema->read(['connection' => 'schema_prefix', 'models' => false]);
        $this->assertTrue(empty($read['tables']));

        $read = $this->Schema->read([
            'connection' => 'test',
            'name' => 'TestApp',
            'models' => ['SchemaComment', 'SchemaTag', 'SchemaPost'],
        ]);
        $this->assertFalse(isset($read['tables']['missing']['posts_tags']), 'Join table marked as missing');
    }

    /**
     * testSchemaReadWithAppModel method
     *
     * @return void
     */
    public function testSchemaReadWithAppModel(): void
    {
        $connections = ConnectionManager::enumConnectionObjects();
        ConnectionManager::drop('default');
        ConnectionManager::create('default', $connections['test']);

        try {
            $result = $this->Schema->read([
                'connection' => 'default',
                'name' => 'TestApp',
                'models' => ['AppModel'],
            ]);

            $this->assertIsArray($result);
            $this->assertArrayHasKey('name', $result);
            $this->assertEquals('TestApp', $result['name']);
        } catch (MissingTableException $mte) {
            ConnectionManager::drop('default');
            $this->fail($mte->getMessage());
        }

        ConnectionManager::drop('default');
    }

    /**
     * test reading schema from plugins.
     *
     * @return void
     */
    public function testSchemaReadWithPlugins(): void
    {
        App::objects('model', null, false);
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');

        $Schema = new CakeSchema();
        $Schema->plugin = 'TestLegacyPlugin';
        $read = $Schema->read([
            'connection' => 'test',
            'name' => 'TestApp',
            'models' => true,
        ]);
        unset($read['tables']['missing']);
        $this->assertTrue(isset($read['tables']['auth_users']));
        $this->assertTrue(isset($read['tables']['authors']));
        $this->assertTrue(isset($read['tables']['test_plugin_comments']));
        $this->assertTrue(isset($read['tables']['posts']));
        $this->assertTrue(count($read['tables']) >= 4);

        App::build();
    }

    /**
     * test loading schema files inside of plugins.
     *
     * @return void
     */
    public function testSchemaLoadingFromPlugin(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $Other = $this->Schema->load(['name' => 'TestPluginApp', 'plugin' => 'TestLegacyPlugin']);
        $this->assertEquals('TestPluginApp', $Other->name);
        $this->assertEquals(['test_plugin_acos'], array_keys($Other->tables));

        App::build();
    }
}
