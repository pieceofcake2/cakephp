<?php
/**
 * Tests cross database HABTM. Requires $test and $test2 to both be set in DATABASE_CONFIG
 * NOTE: When testing on MySQL, you must set 'persistent' => false on *both* database connections,
 * or one connection will step on the other.
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
 * @since         CakePHP(tm) v 2.1
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model;

use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\Database\Sqlite;
use Cake\Utility\ClassRegistry;

/**
 * ModelCrossSchemaHabtmTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelCrossSchemaHabtmTest extends BaseModelTest
{
    /**
     * Fixtures to be used
     *
     * @var array
     */
    public $fixtures = [
        'core.player', 'core.guild', 'core.guilds_player',
        'core.armor', 'core.armors_player',
    ];

    /**
     * Don't drop tables if they exist
     *
     * @var bool
     */
    public $dropTables = false;

    /**
     * Don't auto load fixtures
     *
     * @var bool
     */
    public $autoFixtures = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_checkConfigs();
    }

    /**
     * Check if primary and secondary test databases are configured.
     *
     * @return void
     */
    protected function _checkConfigs(): void
    {
        $config = ConnectionManager::enumConnectionObjects();
        $this->skipIf($this->db instanceof Sqlite, 'This test is not compatible with Sqlite.');
        $this->skipIf(
            !isset($config['test']) || !isset($config['test2']),
            'Primary and secondary test databases not configured, ' .
            'skipping cross-database join tests.' .
            ' To run these tests, you must define $test and $test2 in your database configuration.',
        );
    }

    /**
     * testModelDatasources method
     *
     * @return void
     */
    public function testModelDatasources(): void
    {
        $this->loadFixtures('Player', 'Guild', 'GuildsPlayer');

        $Player = ClassRegistry::init('Player');
        $this->assertEquals('test', $Player->useDbConfig);
        $this->assertEquals('test', $Player->Guild->useDbConfig);
        $this->assertEquals('test2', $Player->GuildsPlayer->useDbConfig);

        $this->assertEquals('test', $Player->getDataSource()->configKeyName);
        $this->assertEquals('test', $Player->Guild->getDataSource()->configKeyName);
        $this->assertEquals('test2', $Player->GuildsPlayer->getDataSource()->configKeyName);
    }
}
