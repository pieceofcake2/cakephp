<?php
/**
 * CakeTestFixture file
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
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP(tm) v 1.2.0.4667
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\TestSuite;

use Cake\Error\CakeException;
use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\Database\Sqlite;
use Cake\Model\Datasource\DboSource;
use Cake\Model\Model;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeTestFixture;
use Cake\Utility\ClassRegistry;

/**
 * CakeTestFixtureTestFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureTestFixture extends CakeTestFixture
{
    /**
     * Name property
     *
     * @var string
     */
    public ?string $name = 'FixtureTest';

    /**
     * Table property
     *
     * @var string
     */
    public ?string $table = 'fixture_tests';

    /**
     * Fields array
     *
     * @var array
     */
    public array $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'name' => ['type' => 'string', 'length' => '255'],
        'created' => ['type' => 'datetime'],
    ];

    /**
     * Records property
     *
     * @var array
     */
    public array $records = [
        ['name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00'],
    ];
}

/**
 * StringTestFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class StringsTestFixture extends CakeTestFixture
{
    /**
     * Name property
     *
     * @var string
     */
    public ?string $name = 'Strings';

    /**
     * Table property
     *
     * @var string
     */
    public ?string $table = 'strings';

    /**
     * Fields array
     *
     * @var array
     */
    public array $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'name' => ['type' => 'string', 'length' => '255'],
        'email' => ['type' => 'string', 'length' => '255'],
        'age' => ['type' => 'integer', 'default' => 10],
    ];

    /**
     * Records property
     *
     * @var array
     */
    public array $records = [
        ['name' => 'Mark Doe', 'email' => 'mark.doe@email.com'],
        ['name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20],
        ['email' => 'jane.doe@email.com', 'name' => 'Jane Doe', 'age' => 30],
    ];
}

/**
 * InvalidTestFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class InvalidTestFixture extends CakeTestFixture
{
    /**
     * Name property
     *
     * @var string
     */
    public ?string $name = 'Invalid';

    /**
     * Table property
     *
     * @var string
     */
    public ?string $table = 'invalid';

    /**
     * Fields array - missing "email" row
     *
     * @var array
     */
    public array $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'name' => ['type' => 'string', 'length' => '255'],
        'age' => ['type' => 'integer', 'default' => 10],
    ];

    /**
     * Records property
     *
     * @var array
     */
    public array $records = [
        ['name' => 'Mark Doe', 'email' => 'mark.doe@email.com'],
        ['name' => 'John Doe', 'email' => 'john.doe@email.com', 'age' => 20],
        ['email' => 'jane.doe@email.com', 'name' => 'Jane Doe', 'age' => 30],
    ];
}

/**
 * CakeTestFixtureImportFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureImportFixture extends CakeTestFixture
{
    /**
     * Name property
     *
     * @var string
     */
    public ?string $name = 'ImportFixture';

    /**
     * Import property
     *
     * @var mixed
     */
    public $import = ['table' => 'fixture_tests', 'connection' => 'fixture_test_suite'];
}

/**
 * CakeTestFixtureDefaultImportFixture class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureDefaultImportFixture extends CakeTestFixture
{
    /**
     * Name property
     *
     * @var string
     */
    public ?string $name = 'ImportFixture';
}

/**
 * FixtureImportTestModel class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class FixtureImportTestModel extends Model
{
    public ?string $name = 'FixtureImport';

    public string|bool|null $useTable = 'fixture_tests';

    public string $useDbConfig = 'test';
}
class_alias(FixtureImportTestModel::class, 'App\\Model\\FixtureImportTestModel');

class FixturePrefixTest extends Model
{
    public ?string $name = 'FixturePrefix';

    public string|bool|null $useTable = '_tests';

    public ?string $tablePrefix = 'fixture';

    public string $useDbConfig = 'test';
}
class_alias(FixturePrefixTest::class, 'App\\Model\\FixturePrefixTest');

/**
 * Test case for CakeTestFixture
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureTest extends CakeTestCase
{
    public ?DboSource $criticDb = null;

    public array $insertMulti = [];

    protected array $_backupConfig = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $methods = array_diff(get_class_methods('DboSource'), ['enabled']);

        $this->criticDb = $this->getMock(DboSource::class, $methods);
        $this->criticDb->fullDebug = true;
        $this->db = ConnectionManager::getDataSource('test');
        $this->_backupConfig = $this->db->config;
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        $this->criticDb = null;
        $this->db->config = $this->_backupConfig;

        parent::tearDown();
    }

    /**
     * testInit
     *
     * @return void
     */
    public function testInit()
    {
        $Fixture = new CakeTestFixtureTestFixture();
        unset($Fixture->table);
        $Fixture->init();
        $this->assertEquals('fixture_tests', $Fixture->table);
        $this->assertEquals('id', $Fixture->primaryKey);

        $Fixture = new CakeTestFixtureTestFixture();
        $Fixture->primaryKey = 'my_random_key';
        $Fixture->init();
        $this->assertEquals('my_random_key', $Fixture->primaryKey);
    }

    /**
     * test that init() correctly sets the fixture table when the connection
     * or model have prefixes defined.
     *
     * @return void
     */
    public function testInitDbPrefix()
    {
        $this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
        $db = ConnectionManager::getDataSource('test');
        $Source = new CakeTestFixtureTestFixture();
        $Source->drop($db);
        $Source->create($db);
        $Source->insert($db);

        $fixture = new CakeTestFixtureTestFixture();
        $expected = ['id', 'name', 'created'];
        $this->assertEquals($expected, array_keys($fixture->fields));

        $config = $db->config;
        $config['prefix'] = 'fixture_test_suite_';
        ConnectionManager::create('fixture_test_suite', $config);

        $fixture->fields = $fixture->records = [];
        $fixture->import = ['table' => 'fixture_tests', 'connection' => 'test', 'records' => true];
        $fixture->init();
        $this->assertEquals(count($fixture->records), count($Source->records));
        $fixture->create(ConnectionManager::getDataSource('fixture_test_suite'));

        $fixture = new CakeTestFixtureImportFixture();
        $fixture->fields = $fixture->records = [];
        $fixture->table = null;
        $fixture->import = ['model' => 'FixtureImportTestModel', 'connection' => 'test'];
        $fixture->init();
        $this->assertEquals(['id', 'name', 'created'], array_keys($fixture->fields));
        $this->assertEquals('fixture_tests', $fixture->table);

        $keys = array_flip(ClassRegistry::keys());
        $this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

        $fixture->drop(ConnectionManager::getDataSource('fixture_test_suite'));
        $Source->drop($db);
    }

    /**
     * test that fixtures don't duplicate the test db prefix.
     *
     * @return void
     */
    public function testInitDbPrefixDuplication()
    {
        $this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
        $db = ConnectionManager::getDataSource('test');
        $backPrefix = $db->config['prefix'];
        $db->config['prefix'] = 'cake_fixture_test_';
        ConnectionManager::create('fixture_test_suite', $db->config);
        $newDb = ConnectionManager::getDataSource('fixture_test_suite');
        $newDb->config['prefix'] = 'cake_fixture_test_';

        $source = new CakeTestFixtureTestFixture();
        $source->create($db);
        $source->insert($db);

        $Fixture = new CakeTestFixtureImportFixture();
        $Fixture->fields = $Fixture->records = [];
        $Fixture->table = null;
        $Fixture->import = ['model' => 'FixtureImportTestModel', 'connection' => 'test'];

        $Fixture->init();
        $this->assertEquals(['id', 'name', 'created'], array_keys($Fixture->fields));
        $this->assertEquals('fixture_tests', $Fixture->table);

        $source->drop($db);
        $db->config['prefix'] = $backPrefix;
    }

    /**
     * test init with a model that has a tablePrefix declared.
     *
     * @return void
     */
    public function testInitModelTablePrefix()
    {
        $this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
        $this->skipIf(!empty($this->db->config['prefix']), 'Cannot run this test, you have a database connection prefix.');

        $Source = new CakeTestFixtureTestFixture();
        $Source->create($this->db);
        $Source->insert($this->db);

        $Fixture = new CakeTestFixtureTestFixture();
        unset($Fixture->table);
        $Fixture->fields = $Fixture->records = [];
        $Fixture->import = ['model' => 'FixturePrefixTest', 'connection' => 'test', 'records' => false];
        $Fixture->init();
        $this->assertEquals('fixture_tests', $Fixture->table);

        $keys = array_flip(ClassRegistry::keys());
        $this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

        $Source->drop($this->db);
    }

    /**
     * testImport
     *
     * @return void
     */
    public function testImport()
    {
        $testSuiteDb = ConnectionManager::getDataSource('test');
        $testSuiteConfig = $testSuiteDb->config;
        ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, ['prefix' => 'new_' . $testSuiteConfig['prefix']]));
        $newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

        $source = new CakeTestFixtureTestFixture();
        $source->create($newTestSuiteDb);
        $source->insert($newTestSuiteDb);

        $fixture = new CakeTestFixtureDefaultImportFixture();
        $fixture->fields = $fixture->records = [];
        $fixture->import = ['model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite'];
        $fixture->init();
        $this->assertEquals(['id', 'name', 'created'], array_keys($fixture->fields));

        $keys = array_flip(ClassRegistry::keys());
        $this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

        $source->drop($newTestSuiteDb);
    }

    /**
     * test that importing with records works. Make sure to try with postgres as its
     * handling of aliases is a workaround at best.
     *
     * @return void
     */
    public function testImportWithRecords()
    {
        $testSuiteDb = ConnectionManager::getDataSource('test');
        $testSuiteConfig = $testSuiteDb->config;
        ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, ['prefix' => 'new_' . $testSuiteConfig['prefix']]));
        $newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

        $source = new CakeTestFixtureTestFixture();
        $source->create($newTestSuiteDb);
        $source->insert($newTestSuiteDb);

        $fixture = new CakeTestFixtureDefaultImportFixture();
        $fixture->fields = $fixture->records = [];
        $fixture->import = [
            'model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite', 'records' => true,
        ];
        $fixture->init();
        $this->assertEquals(['id', 'name', 'created'], array_keys($fixture->fields));
        $this->assertFalse(empty($fixture->records[0]), 'No records loaded on importing fixture.');
        $this->assertTrue(isset($fixture->records[0]['name']), 'No name loaded for first record');

        $source->drop($newTestSuiteDb);
    }

    /**
     * test create method
     *
     * @return void
     */
    public function testCreate()
    {
        $Fixture = new CakeTestFixtureTestFixture();
        $this->criticDb->expects($this->atLeastOnce())->method('execute');
        $this->criticDb->expects($this->atLeastOnce())->method('createSchema');
        $return = $Fixture->create($this->criticDb);
        $this->assertTrue($this->criticDb->fullDebug);
        $this->assertTrue($return);

        unset($Fixture->fields);
        $return = $Fixture->create($this->criticDb);
        $this->assertFalse($return);
    }

    /**
     * test the insert method
     *
     * @return void
     */
    public function testInsert()
    {
        $Fixture = new CakeTestFixtureTestFixture();
        $this->criticDb->expects($this->atLeastOnce())
            ->method('insertMulti')
            ->will($this->returnCallback([$this, 'insertCallback']));

        $return = $Fixture->insert($this->criticDb);
        $this->assertTrue(!empty($this->insertMulti));
        $this->assertTrue($this->criticDb->fullDebug);
        $this->assertTrue($return);
        $this->assertEquals('fixture_tests', $this->insertMulti['table']);
        $this->assertEquals(['name', 'created'], $this->insertMulti['fields']);
        $expected = [
            ['Gandalf', '2009-04-28 19:20:00'],
            ['Captain Picard', '2009-04-28 19:20:00'],
            ['Chewbacca', '2009-04-28 19:20:00'],
        ];
        $this->assertEquals($expected, $this->insertMulti['values']);
    }

    /**
     * Helper function to be used as callback and store the parameters of an insertMulti call
     *
     * @param string $table
     * @param string $fields
     * @param array $values
     * @return bool true
     */
    public function insertCallback($table, $fields, array $values): bool
    {
        $this->insertMulti['table'] = $table;
        $this->insertMulti['fields'] = $fields;
        $this->insertMulti['values'] = $values;
        $this->insertMulti['fields_values'] = [];
        foreach ($values as $record) {
            $this->insertMulti['fields_values'][] = array_combine($fields, $record);
        }

        return true;
    }

    /**
     * test the insert method
     *
     * @return void
     */
    public function testInsertStrings()
    {
        $Fixture = new StringsTestFixture();
        $this->criticDb->expects($this->atLeastOnce())
            ->method('insertMulti')
            ->will($this->returnCallback([$this, 'insertCallback']));

        $return = $Fixture->insert($this->criticDb);
        $this->assertTrue($this->criticDb->fullDebug);
        $this->assertTrue($return);
        $this->assertEquals('strings', $this->insertMulti['table']);
        $this->assertEquals(['name', 'email', 'age'], array_values($this->insertMulti['fields']));
        $expected = [
            ['Mark Doe', 'mark.doe@email.com', null],
            ['John Doe', 'john.doe@email.com', 20],
            ['Jane Doe', 'jane.doe@email.com', 30],
        ];
        $this->assertEquals($expected, $this->insertMulti['values']);
        $expected = [
            [
                'name' => 'Mark Doe',
                'email' => 'mark.doe@email.com',
                'age' => null,
            ],
            [
                'name' => 'John Doe',
                'email' => 'john.doe@email.com',
                'age' => 20,
            ],
            [
                'name' => 'Jane Doe',
                'email' => 'jane.doe@email.com',
                'age' => 30,
            ],
        ];
        $this->assertEquals($expected, $this->insertMulti['fields_values']);
    }

    /**
     * test the insert method with invalid fixture
     *
     * @return void
     */
    public function testInsertInvalid()
    {
        $this->expectException(CakeException::class);
        $Fixture = new InvalidTestFixture();
        $Fixture->insert($this->criticDb);
    }

    /**
     * Test the drop method
     *
     * @return void
     */
    public function testDrop()
    {
        $fixture = new CakeTestFixtureTestFixture();
        $this->criticDb
            ->expects($this->exactly(2))
            ->method('execute')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->criticDb
            ->expects($this->exactly(2))
            ->method('dropSchema');

        $return = $fixture->drop($this->criticDb);
        $this->assertTrue($this->criticDb->fullDebug);
        $this->assertTrue($return);

        $return = $fixture->drop($this->criticDb);
        $this->assertTrue($return);

        unset($fixture->fields);
        $return = $fixture->drop($this->criticDb);
        $this->assertFalse($return);
    }

    /**
     * Test the truncate method.
     *
     * @return void
     */
    public function testTruncate()
    {
        $fixture = new CakeTestFixtureTestFixture();
        $this->criticDb->expects($this->atLeastOnce())->method('truncate');
        $fixture->truncate($this->criticDb);
        $this->assertTrue($this->criticDb->fullDebug);
    }
}
