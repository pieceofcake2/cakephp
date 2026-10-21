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

namespace Cake\Test\TestCase\Legacy\TestSuite;

use Cake\Core\App;
use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\Database\Sqlite;
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
    public $name = 'FixtureTest';

    /**
     * Table property
     *
     * @var string
     */
    public $table = 'fixture_tests';

    /**
     * Fields array
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'name' => ['type' => 'string', 'length' => '255'],
        'created' => ['type' => 'datetime'],
    ];

    /**
     * Records property
     *
     * @var array
     */
    public $records = [
        ['name' => 'Gandalf', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Captain Picard', 'created' => '2009-04-28 19:20:00'],
        ['name' => 'Chewbacca', 'created' => '2009-04-28 19:20:00'],
    ];
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
    public $name = 'ImportFixture';
}

/**
 * Test case for CakeTestFixture
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestFixtureTest extends CakeTestCase
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
        $this->db->config = $this->_backupConfig;

        parent::tearDown();
    }

    /**
     * testImport
     *
     * @return void
     */
    public function testImport(): void
    {
        $testSuiteDb = ConnectionManager::getDataSource('test');
        $testSuiteConfig = $testSuiteDb->config;
        ConnectionManager::create('new_test_suite', array_merge($testSuiteConfig, ['prefix' => 'new_' . $testSuiteConfig['prefix']]));
        $newTestSuiteDb = ConnectionManager::getDataSource('new_test_suite');

        $Source = new CakeTestFixtureTestFixture();
        $Source->create($newTestSuiteDb);
        $Source->insert($newTestSuiteDb);

        $Fixture = new CakeTestFixtureDefaultImportFixture();
        $Fixture->fields = $Fixture->records = null;
        $Fixture->import = ['model' => 'FixtureImportTestModel', 'connection' => 'new_test_suite'];
        $Fixture->init();
        $this->assertEquals(['id', 'name', 'created'], array_keys($Fixture->fields));

        $keys = array_flip(ClassRegistry::keys());
        $this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

        $Source->drop($newTestSuiteDb);
    }

    /**
     * test init with a model that has a tablePrefix declared.
     *
     * @return void
     */
    public function testInitModelTablePrefix(): void
    {
        $this->skipIf($this->db instanceof Sqlite, 'Cannot open 2 connections to Sqlite');
        $this->skipIf(!empty($this->db->config['prefix']), 'Cannot run this test, you have a database connection prefix.');

        $Source = new CakeTestFixtureTestFixture();
        $Source->create($this->db);
        $Source->insert($this->db);

        $Fixture = new CakeTestFixtureTestFixture();
        unset($Fixture->table);
        $Fixture->fields = $Fixture->records = null;
        $Fixture->import = ['model' => 'FixturePrefixTest', 'connection' => 'test', 'records' => false];
        $Fixture->init();
        $this->assertEquals('fixture_tests', $Fixture->table);

        $keys = array_flip(ClassRegistry::keys());
        $this->assertFalse(array_key_exists('fixtureimporttestmodel', $keys));

        $Source->drop($this->db);
    }
}
