<?php

/**
 * CakeFixtureManager file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite.Fixture
 * @since         CakePHP v 2.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite\Fixture;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingDatasourceConfigException;
use Cake\Model\CakeSchema;
use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\DboSource;
use Cake\Test\Fixture\UuidFixture;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeFixtureManager;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use TestApp\Test\Fixture\FixtureManagerPostFixture;
use TestApp\Test\Fixture\NamespacedArticleFixture;
use TestTwoPlugin\Test\Fixture\NamespacedArticleFixture as PluginNamespacedArticleFixture;
use TestTwoPlugin\Test\Fixture\NamespacedCommentFixture;
use UnexpectedValueException;

/**
 * Test Case for CakeFixtureManager class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeFixtureManagerTest extends CakeTestCase
{
    /**
     * reset environment.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        // The fixture class is only loaded by the fixture manager, so it is missing when this test runs on its own.
        require_once CORE_TESTS . DS . 'Fixture' . DS . 'UuidFixture.php';
        $this->fixtureManager = new CakeFixtureManager();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        $db = ConnectionManager::getDataSource('test');
        // The tests create these tables; drop them so that a failing test leaves nothing behind.
        $db->execute('DROP TABLE IF EXISTS ' . $db->fullTableName('fixture_manager_posts'));
        $db->execute('DROP TABLE IF EXISTS ' . $db->fullTableName('namespaced_articles'));
        $db->execute('DROP TABLE IF EXISTS ' . $db->fullTableName('plugin_namespaced_articles'));
        unset($this->fixtureManager);
        CakePlugin::unload('TestTwoPlugin');
        CakePlugin::unload('TestLegacyPlugin');

        parent::tearDown();
    }

    /**
     * testLoadTruncatesTable
     *
     * @return void
     */
    public function testLoadTruncatesTable(): void
    {
        $MockFixture = $this->getMock(UuidFixture::class, ['truncate', 'insert']);
        // The table name is derived from the class name, which is the mock class name here.
        $MockFixture->table = 'uuids';
        $MockFixture
            ->expects($this->once())
            ->method('truncate')
            ->will($this->returnValue(true));
        $MockFixture
            ->expects($this->once())
            ->method('insert')
            ->will($this->returnValue(true));

        // The table has to exist for it to be truncated instead of created.
        $db = ConnectionManager::getDataSource($MockFixture->useDbConfig);
        $cacheSources = $db->cacheSources;
        $db->cacheSources = false;
        $Fixture = null;
        if (!in_array($db->config['prefix'] . $MockFixture->table, (array)$db->listSources())) {
            $Fixture = new UuidFixture();
            $Fixture->create($db);
        }

        $fixtureManager = $this->fixtureManager;
        $fixtureManagerReflection = new ReflectionClass($fixtureManager);

        $loadedProperty = $fixtureManagerReflection->getProperty('_loaded');
        if (PHP_VERSION_ID < 80100) {
            $loadedProperty->setAccessible(true);
        }
        $loadedProperty->setValue($fixtureManager, ['core.uuid' => $MockFixture]);

        $TestCase = $this->getMock(CakeTestCase::class);
        $TestCase->fixtures = ['core.uuid'];
        $TestCase->autoFixtures = true;
        $TestCase->dropTables = false;

        try {
            $fixtureManager->load($TestCase);
        } finally {
            if ($Fixture) {
                $Fixture->drop($db);
            }
            $db->cacheSources = $cacheSources;
        }
    }

    /**
     * testLoadSingleTruncatesTable
     *
     * @return void
     */
    public function testLoadSingleTruncatesTable(): void
    {
        $MockFixture = $this->getMock(UuidFixture::class, ['truncate']);
        // The table name is derived from the class name, which is the mock class name here.
        $MockFixture->table = 'uuids';
        $MockFixture
            ->expects($this->once())
            ->method('truncate')
            ->will($this->returnValue(true));

        $fixtureManager = $this->fixtureManager;
        $fixtureManagerReflection = new ReflectionClass($fixtureManager);

        $fixtureMapProperty = $fixtureManagerReflection->getProperty('_fixtureMap');
        if (PHP_VERSION_ID < 80100) {
            $fixtureMapProperty->setAccessible(true);
        }
        $fixtureMapProperty->setValue($fixtureManager, ['UuidFixture' => $MockFixture]);

        $dboMethods = array_diff(get_class_methods(DboSource::class), ['enabled']);
        $db = $this->getMock(DboSource::class, $dboMethods);
        $db->config['prefix'] = '';
        $db->expects($this->any())
            ->method('listSources')
            ->will($this->returnValue([$MockFixture->table]));

        $fixtureManager->loadSingle('Uuid', $db, false);
    }

    /**
     * Test that fixtures are found in the namespaces of the application and of plugins.
     *
     * @return void
     */
    public function testLoadNamespacedFixtures(): void
    {
        Configure::write('App.namespace', 'TestApp');
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]], App::RESET);
        CakePlugin::load('TestTwoPlugin');

        $loadFixtures = new ReflectionMethod($this->fixtureManager, '_loadFixtures');
        if (PHP_VERSION_ID < 80100) {
            $loadFixtures->setAccessible(true);
        }
        $loadFixtures->invoke($this->fixtureManager, ['app.namespaced_article', 'namespaced_article', 'plugin.test_two_plugin.namespaced_comment']);
        $loaded = new ReflectionProperty($this->fixtureManager, '_loaded');
        if (PHP_VERSION_ID < 80100) {
            $loaded->setAccessible(true);
        }
        $fixtures = $loaded->getValue($this->fixtureManager);

        $this->assertInstanceOf(NamespacedArticleFixture::class, $fixtures['app.namespaced_article']);
        $this->assertInstanceOf(NamespacedArticleFixture::class, $fixtures['namespaced_article']);
        $this->assertInstanceOf(NamespacedCommentFixture::class, $fixtures['plugin.test_two_plugin.namespaced_comment']);

        // The name and the table come from the class name without the namespace
        $this->assertSame('NamespacedArticle', $fixtures['app.namespaced_article']->name);
        $this->assertSame('namespaced_articles', $fixtures['app.namespaced_article']->table);
        $this->assertSame('namespaced_comments', $fixtures['plugin.test_two_plugin.namespaced_comment']->table);
    }

    /**
     * A namespaced fixture that Composer does not load is read from its file, as the core fixtures are in a package
     * that depends on CakePHP (core.article in a plugin), and its namespaced class is used; a file with a global
     * class gives that class
     *
     * @return void
     */
    public function testLoadNamespacedFixtureFromItsFile(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS, CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]], App::RESET);
        CakePlugin::load(['TestTwoPlugin', 'TestLegacyPlugin']);
        $this->assertFalse(class_exists('TestTwoPlugin\\Test\\Fixture\\FallbackPostFixture'));

        $loadFixtures = new ReflectionMethod($this->fixtureManager, '_loadFixtures');
        if (PHP_VERSION_ID < 80100) {
            $loadFixtures->setAccessible(true);
        }
        $loadFixtures->invoke($this->fixtureManager, ['plugin.test_two_plugin.fallback_post', 'plugin.test_legacy_plugin.legacy_comment']);
        $loaded = new ReflectionProperty($this->fixtureManager, '_loaded');
        if (PHP_VERSION_ID < 80100) {
            $loaded->setAccessible(true);
        }
        $fixtures = $loaded->getValue($this->fixtureManager);

        $this->assertInstanceOf('TestTwoPlugin\\Test\\Fixture\\FallbackPostFixture', $fixtures['plugin.test_two_plugin.fallback_post']);
        $this->assertSame('fallback_posts', $fixtures['plugin.test_two_plugin.fallback_post']->table);
        // A file that declares a global class, as before 2.13
        $this->assertInstanceOf('LegacyCommentFixture', $fixtures['plugin.test_legacy_plugin.legacy_comment']);
    }

    /**
     * Builds a test case that uses the given fixtures.
     *
     * @param array|string $fixtures The fixtures of the test case.
     * @param bool $dropTables Whether the tables are dropped before they are created.
     * @param bool $autoFixtures Whether the fixtures are loaded before each test.
     * @return CakeTestCase
     */
    protected function _testCaseWith($fixtures, $dropTables = true, $autoFixtures = true): CakeTestCase
    {
        $test = $this->getMock(CakeTestCase::class);
        $test->fixtures = $fixtures;
        $test->dropTables = $dropTables;
        $test->autoFixtures = $autoFixtures;

        return $test;
    }

    /**
     * Creates the fixture_manager_posts table with an extra column that the fixture schema does not
     * have. The datasource builds the table, so that the primary key gets the sequence or the
     * identity of the database (PostgreSQL, SQL Server).
     *
     * @param DboSource $db The datasource
     * @param bool $nullTitle Whether the title column allows null
     * @return void
     */
    protected function _createPostsTable(DboSource $db, bool $nullTitle): void
    {
        $schema = new CakeSchema(['name' => 'FixtureManagerTest']);
        $schema->tables = [
            'fixture_manager_posts' => [
                'id' => ['type' => 'integer', 'key' => 'primary'],
                'title' => ['type' => 'string', 'length' => 255, 'null' => $nullTitle],
                'extra' => ['type' => 'string', 'length' => 10, 'null' => true],
            ],
        ];
        $db->execute($db->createSchema($schema));
        $db->cacheSources = false;
    }

    /**
     * Returns the titles in the fixture_manager_posts table, ordered by id.
     *
     * @return array<int, string> The titles, indexed by id.
     */
    protected function _postTitles(): array
    {
        $db = ConnectionManager::getDataSource('test');
        $rows = $db->fetchAll('SELECT id, title FROM ' . $db->fullTableName('fixture_manager_posts') . ' ORDER BY id', false);
        $titles = [];
        foreach ($rows as $row) {
            $row = reset($row);
            $titles[(int)$row['id']] = $row['title'];
        }

        return $titles;
    }

    /**
     * Returns whether the fixture_manager_posts table exists in the test database.
     *
     * @return bool
     */
    protected function _postsTableExists(): bool
    {
        $db = ConnectionManager::getDataSource('test');

        return in_array($db->config['prefix'] . 'fixture_manager_posts', (array)$db->listSources(), true);
    }

    /**
     * Test that fixturize() splits a comma separated list of fixtures, gives the test case the
     * test datasource, and that load() then creates the tables and inserts the records.
     *
     * @return void
     */
    public function testFixturizeAndLoadCreateTablesWithRecords(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $test = $this->_testCaseWith('app.fixture_manager_post , app.namespaced_article');

        $this->fixtureManager->fixturize($test);

        $this->assertSame(['app.fixture_manager_post', 'app.namespaced_article'], $test->fixtures);
        $this->assertSame(ConnectionManager::getDataSource('test'), $test->db);
        $this->assertFalse($this->_postsTableExists());

        $this->fixtureManager->load($test);

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());
        $db = ConnectionManager::getDataSource('test');
        $this->assertContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());

        $this->fixtureManager->shutDown();
        $this->assertNotContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());
    }

    /**
     * Test that the fixtures of a test case can be given by their class names, with or
     * without the Fixture suffix, and that load() creates their tables.
     *
     * @return void
     */
    public function testFixturizeAndLoadFixturesByClassName(): void
    {
        $test = $this->_testCaseWith([FixtureManagerPostFixture::class, 'TestApp\Test\Fixture\NamespacedArticle']);

        $this->fixtureManager->fixturize($test);
        $this->fixtureManager->load($test);

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());
        $db = ConnectionManager::getDataSource('test');
        $this->assertContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());

        $this->fixtureManager->shutDown();
        $this->assertFalse($this->_postsTableExists());
    }

    /**
     * Test that loadSingle() takes the class name of a fixture as well as its name.
     *
     * @return void
     */
    public function testLoadSingleByClassName(): void
    {
        $test = $this->_testCaseWith([FixtureManagerPostFixture::class, NamespacedArticleFixture::class], true, false);
        $this->fixtureManager->fixturize($test);
        $this->assertFalse($this->_postsTableExists());

        $this->fixtureManager->loadSingle(FixtureManagerPostFixture::class);
        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());

        $this->fixtureManager->loadSingle('NamespacedArticle');
        $db = ConnectionManager::getDataSource('test');
        $this->assertContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());

        $this->fixtureManager->shutDown();
    }

    /**
     * Test that a class name of a fixture that does not exist is reported with the class name.
     *
     * @return void
     */
    public function testFixturizeWithMissingClassName(): void
    {
        $test = $this->_testCaseWith(['TestApp\Test\Fixture\Missing']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Referenced fixture class TestApp\Test\Fixture\MissingFixture not found');
        $this->fixtureManager->fixturize($test);
    }

    /**
     * Test that loadSingle() with a class name loads exactly that class, also when a fixture
     * of another namespace has the same name, and ignores a leading backslash.
     *
     * @return void
     */
    public function testLoadSingleByClassNameWithTheSameNameInAnotherNamespace(): void
    {
        $test = $this->_testCaseWith([NamespacedArticleFixture::class, PluginNamespacedArticleFixture::class], true, false);
        $this->fixtureManager->fixturize($test);
        $db = ConnectionManager::getDataSource('test');

        $this->fixtureManager->loadSingle('\\' . NamespacedArticleFixture::class);

        $this->assertContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());
        $this->assertNotContains($db->config['prefix'] . 'plugin_namespaced_articles', $db->listSources());

        $this->fixtureManager->loadSingle(PluginNamespacedArticleFixture::class);

        $this->assertContains($db->config['prefix'] . 'plugin_namespaced_articles', $db->listSources());

        $this->fixtureManager->shutDown();
        $this->assertNotContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());
        $this->assertNotContains($db->config['prefix'] . 'plugin_namespaced_articles', $db->listSources());
    }

    /**
     * Test that loadSingle() rejects a class that is not among the fixtures of the test case,
     * although a fixture of another namespace has the same name.
     *
     * @return void
     */
    public function testLoadSingleByClassNameNotAmongTheFixtures(): void
    {
        $test = $this->_testCaseWith([NamespacedArticleFixture::class], true, false);
        $this->fixtureManager->fixturize($test);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Referenced fixture class ' . PluginNamespacedArticleFixture::class . ' not found');
        $this->fixtureManager->loadSingle(PluginNamespacedArticleFixture::class);
    }

    /**
     * Test that a fixture given in several notations has one instance, whose table load()
     * fills once and shutDown() drops.
     *
     * @return void
     */
    public function testFixtureInSeveralNotationsHasOneInstance(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $names = ['app.fixture_manager_post', FixtureManagerPostFixture::class, '\\TestApp\\Test\\Fixture\\FixtureManagerPost'];
        $test = $this->_testCaseWith($names);
        $loaded = new ReflectionProperty($this->fixtureManager, '_loaded');
        if (PHP_VERSION_ID < 80100) {
            $loaded->setAccessible(true);
        }

        $this->fixtureManager->fixturize($test);

        $fixtures = $loaded->getValue($this->fixtureManager);
        $this->assertSame($names, array_keys($fixtures));
        $this->assertSame($fixtures[$names[0]], $fixtures[$names[1]]);
        $this->assertSame($fixtures[$names[0]], $fixtures[$names[2]]);

        $this->fixtureManager->load($test);

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());
        $this->assertSame(['test'], $fixtures[$names[0]]->created);

        $this->fixtureManager->shutDown();

        $this->assertFalse($this->_postsTableExists());
        $this->assertSame([], $fixtures[$names[0]]->created);
    }

    /**
     * Test that CakeTestCase::loadFixtures() with a class name loads exactly that class.
     *
     * @return void
     */
    public function testLoadFixturesOfTestCaseByClassName(): void
    {
        // Mocking only setUp() keeps the original loadFixtures().
        $test = $this->getMock(CakeTestCase::class, ['setUp']);
        $test->fixtures = [NamespacedArticleFixture::class, PluginNamespacedArticleFixture::class];
        $test->autoFixtures = false;
        $test->fixtureManager = $this->fixtureManager;
        $this->fixtureManager->fixturize($test);
        $db = ConnectionManager::getDataSource('test');

        $test->loadFixtures(NamespacedArticleFixture::class);

        $this->assertContains($db->config['prefix'] . 'namespaced_articles', $db->listSources());
        $this->assertNotContains($db->config['prefix'] . 'plugin_namespaced_articles', $db->listSources());

        $this->fixtureManager->shutDown();
    }

    /**
     * Test that unload() empties the tables created by the fixtures but keeps them, and that
     * shutDown() drops them.
     *
     * @return void
     */
    public function testUnloadTruncatesAndShutDownDropsTables(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $test = $this->_testCaseWith(['app.fixture_manager_post']);
        $this->fixtureManager->fixturize($test);
        $this->fixtureManager->load($test);
        $db = ConnectionManager::getDataSource('test');
        $db->execute('INSERT INTO ' . $db->fullTableName('fixture_manager_posts') . " (title) VALUES ('Added by the test')");

        $this->fixtureManager->unload($test);

        $this->assertTrue($this->_postsTableExists());
        $this->assertSame([], $this->_postTitles());

        $this->fixtureManager->shutDown();

        $this->assertFalse($this->_postsTableExists());
    }

    /**
     * Test that with dropTables disabled an existing table is kept and only emptied, and that
     * the table is remembered as created, so that the next load() truncates it again without
     * creating it: the extra column that the fixture schema does not have is still there after
     * both loads.
     *
     * @return void
     */
    public function testLoadWithoutDropTablesTruncatesExistingTable(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $db = ConnectionManager::getDataSource('test');
        $this->_createPostsTable($db, false);
        $db->execute('INSERT INTO ' . $db->fullTableName('fixture_manager_posts') . " (title) VALUES ('Old row')");
        $test = $this->_testCaseWith(['app.fixture_manager_post'], false);
        $this->fixtureManager->fixturize($test);

        $this->fixtureManager->load($test);

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());
        // The table was not re-created from the fixture schema, so the extra column is still there.
        $this->assertArrayHasKey('extra', $db->describe('fixture_manager_posts'));

        $db->execute('INSERT INTO ' . $db->fullTableName('fixture_manager_posts') . " (title) VALUES ('Added by the test')");
        $this->fixtureManager->load($test);

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());
        $this->assertArrayHasKey('extra', $db->describe('fixture_manager_posts'));

        // The kept table counts as created by the fixture, so shutDown() drops it.
        $this->fixtureManager->shutDown();
        $this->assertFalse($this->_postsTableExists());
    }

    /**
     * Test that with dropTables enabled an existing table is dropped and created again from the
     * fixture schema.
     *
     * @return void
     */
    public function testLoadWithDropTablesRecreatesExistingTable(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $db = ConnectionManager::getDataSource('test');
        $this->_createPostsTable($db, true);
        $db->execute('INSERT INTO ' . $db->fullTableName('fixture_manager_posts') . " (title) VALUES ('Old row')");
        $test = $this->_testCaseWith(['app.fixture_manager_post'], true);
        $this->fixtureManager->fixturize($test);

        $this->fixtureManager->load($test);

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());
        $this->assertSame(['id', 'title'], array_keys($db->describe('fixture_manager_posts')));
    }

    /**
     * Test that the fixtures of a test case class are processed only once, so that a later
     * change to its fixtures is not loaded again.
     *
     * @return void
     */
    public function testFixturizeProcessesEachTestCaseClassOnce(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $test = $this->_testCaseWith(['app.fixture_manager_post']);
        $this->fixtureManager->fixturize($test);

        $test->fixtures = ['app.this_fixture_does_not_exist'];
        $test->db = null;
        $this->fixtureManager->fixturize($test);

        $this->assertSame(ConnectionManager::getDataSource('test'), $test->db);
    }

    /**
     * Test that a fixture that two test case classes use is instantiated once and shared, and
     * that the second test case gets the same datasource.
     *
     * @return void
     */
    public function testFixturizeSharesFixturesBetweenTestCases(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $first = $this->_testCaseWith(['app.fixture_manager_post']);
        // fixturize() processes each test case class once, so the second test case must be of
        // another class: mocking other methods than _testCaseWith() does gives it its own mock class.
        $second = $this->getMock(CakeTestCase::class, ['setUp']);
        $second->fixtures = ['app.fixture_manager_post', 'app.namespaced_article'];
        $loaded = new ReflectionProperty($this->fixtureManager, '_loaded');
        if (PHP_VERSION_ID < 80100) {
            $loaded->setAccessible(true);
        }

        $this->fixtureManager->fixturize($first);
        $post = $loaded->getValue($this->fixtureManager)['app.fixture_manager_post'];
        $this->fixtureManager->fixturize($second);

        $fixtures = $loaded->getValue($this->fixtureManager);
        $this->assertSame(['app.fixture_manager_post', 'app.namespaced_article'], array_keys($fixtures));
        $this->assertSame($post, $fixtures['app.fixture_manager_post']);
        $this->assertSame($first->db, $second->db);
        $this->assertSame(ConnectionManager::getDataSource('test'), $second->db);
    }

    /**
     * Test that loadSingle() creates the table of a fixture that is not loaded automatically,
     * and that it rejects a fixture that was not fixturized.
     *
     * @return void
     */
    public function testLoadSingle(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $test = $this->_testCaseWith(['app.fixture_manager_post'], true, false);
        $this->fixtureManager->fixturize($test);
        $this->fixtureManager->load($test);
        $this->assertFalse($this->_postsTableExists());

        $this->fixtureManager->loadSingle('FixtureManagerPost');

        $this->assertSame([1 => 'First Post', 2 => 'Second Post'], $this->_postTitles());

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage('Referenced fixture class NamespacedArticleFixture not found');
        $this->fixtureManager->loadSingle('NamespacedArticle');
    }

    /**
     * Test that a fixture that does not exist is reported with its class name and the path of
     * the file it was looked for in, including the sub-directory.
     *
     * @return void
     */
    public function testFixturizeMissingFixture(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $test = $this->_testCaseWith(['app.fixture_manager_post', 'app.sub_dir/missing_post']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Referenced fixture class MissingPost (' .
            str_replace([APP, CAKE_CORE_INCLUDE_PATH, ROOT], '', TESTS . 'Fixture' . DS . 'sub_dir' . DS . 'MissingPostFixture.php') .
            ') not found',
        );
        $this->fixtureManager->fixturize($test);
    }

    /**
     * Test that a missing core fixture is reported with the core fixture path, also when a
     * plugin fixture is loaded before it.
     *
     * @return void
     */
    public function testFixturizeMissingCoreFixtureAfterPluginFixture(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]], App::RESET);
        CakePlugin::load('TestTwoPlugin');
        $test = $this->_testCaseWith(['plugin.test_two_plugin.namespaced_comment', 'core.this_fixture_does_not_exist']);

        $this->expectException(UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'Referenced fixture class ThisFixtureDoesNotExist (' .
            str_replace([APP, CAKE_CORE_INCLUDE_PATH, ROOT], '', CORE_TESTS . DS . 'Fixture' . DS . 'ThisFixtureDoesNotExistFixture.php') .
            ') not found',
        );
        $this->fixtureManager->fixturize($test);
    }

    /**
     * Test that loading a fixture whose connection is not configured fails with the name of
     * the connection.
     *
     * @return void
     */
    public function testLoadMissingConnection(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $test = $this->_testCaseWith(['app.missing_connection']);
        $this->fixtureManager->fixturize($test);

        $this->expectException(MissingDatasourceConfigException::class);
        $this->expectExceptionMessage('The datasource configuration "test_missing_connection" was not found in database.php');
        $this->fixtureManager->load($test);
    }
}
