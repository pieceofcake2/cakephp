<?php
/**
 * A factory class to manage the life cycle of test fixtures
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.TestSuite.Fixture
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\TestSuite\Fixture;

use Cake\Core\CakePlugin;
use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\DboSource;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Inflector;
use UnexpectedValueException;

/**
 * A factory class to manage the life cycle of test fixtures
 *
 * @package       Cake.TestSuite.Fixture
 */
class CakeFixtureManager
{
    /**
     * Was this class already initialized?
     *
     * @var bool
     */
    protected bool $_initialized = false;

    /**
     * Default datasource to use
     *
     * @var DboSource|null
     */
    protected ?DboSource $_db = null;

    /**
     * Holds the fixture classes that where instantiated
     *
     * @var array<CakeTestFixture>
     */
    protected array $_loaded = [];

    /**
     * Holds the fixture classes that where instantiated indexed by class name
     *
     * @var array
     */
    protected array $_fixtureMap = [];

    /**
     * @var array<class-string, bool>
     */
    protected array $_processed = [];

    /**
     * Inspects the test to look for unloaded fixtures and loads them
     *
     * @param CakeTestCase $test the test case to inspect
     * @return void
     */
    public function fixturize(CakeTestCase $test): void
    {
        if (!$this->_initialized) {
            ClassRegistry::config(['ds' => 'test', 'testing' => true]);
        }
        if (empty($test->fixtures) || !empty($this->_processed[$test::class])) {
            $test->db = $this->_db;

            return;
        }

        $this->_initDb();
        $test->db = $this->_db;
        $this->_loadFixtures($test->fixtures);
        $this->_processed[$test::class] = true;
    }

    /**
     * Initializes this class with a DataSource object to use as default for all fixtures
     *
     * @return void
     */
    protected function _initDb(): void
    {
        if ($this->_initialized) {
            return;
        }
        $db = ConnectionManager::getDboSource('test');
        $db->cacheSources = false;
        $this->_db = $db;
        $this->_initialized = true;
    }

    /**
     * Parse the fixture path included in test cases, to get the fixture class name, and the
     * real fixture path including sub-directories
     *
     * @param string $fixturePath the fixture path to parse
     * @return array{
     *     fixture: string,
     *     additionalPath: string
     * } containing fixture class name and optional additional path
     */
    protected function _parseFixturePath(string $fixturePath): array
    {
        $pathTokenArray = explode('/', $fixturePath);
        $fixture = array_pop($pathTokenArray);
        $additionalPath = '';
        foreach ($pathTokenArray as $pathToken) {
            $additionalPath .= DS . $pathToken;
        }

        return ['fixture' => $fixture, 'additionalPath' => $additionalPath];
    }

    /**
     * Looks for fixture files and instantiates the classes accordingly
     *
     * @param array<string> $fixtures the fixture names to load using the notation {type}.{name}
     * @return void
     * @throws UnexpectedValueException when a referenced fixture does not exist.
     */
    protected function _loadFixtures(array $fixtures): void
    {
        foreach ($fixtures as $fixture) {
            $fixtureFile = null;
            $fixtureIndex = $fixture;
            if (isset($this->_loaded[$fixture])) {
                continue;
            }

            $fixtureType = null;
            $pluginName = null;
            $fixturePaths = [];

            if (str_starts_with($fixture, 'core.')) {
                $fixtureType = 'core';
                $fixture = substr($fixture, strlen('core.'));
                $fixturePaths = [CORE_TESTS . DS . 'Fixture'];
            } elseif (str_starts_with($fixture, 'app.')) {
                $fixtureType = 'app';
                $fixturePrefixLess = substr($fixture, strlen('app.'));
                $fixtureParsedPath = $this->_parseFixturePath($fixturePrefixLess);
                $fixture = $fixtureParsedPath['fixture'];
                $fixturePaths = [
                    TESTS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                ];
            } elseif (str_starts_with($fixture, 'plugin.')) {
                $fixtureType = 'plugin';
                $explodedFixture = explode('.', $fixture, 3);
                $pluginName = Inflector::camelize($explodedFixture[1]);
                $fixtureParsedPath = $this->_parseFixturePath($explodedFixture[2]);
                $fixture = $fixtureParsedPath['fixture'];
                $fixturePaths = [
                    CakePlugin::path($pluginName) . 'tests' . DS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                    CakePlugin::path($pluginName) . 'Test' . DS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                    TESTS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                ];
            } else {
                $fixturePaths = [
                    TESTS . 'Fixture',
                    CORE_TESTS . DS . 'Fixture',
                ];
            }

            $className = Inflector::camelize($fixture);
            $fixtureClass = $className . 'Fixture';

            // Try modern (namespaced) approach first using PSR-4 autoloading
            $namespacedClasses = [];
            if ($fixtureType === 'core') {
                $namespacedClasses[] = 'Cake\\Test\\Fixture\\' . $fixtureClass;
            } elseif ($fixtureType === 'app') {
                $namespacedClasses[] = 'App\\Test\\Fixture\\' . $fixtureClass;
            } elseif ($fixtureType === 'plugin' && $pluginName) {
                $namespacedClasses[] = $pluginName . '\\Test\\Fixture\\' . $fixtureClass;
                $namespacedClasses[] = 'Cake\\' . $pluginName . '\\Test\\Fixture\\' . $fixtureClass;
            } else {
                // For unspecified type, try both app and core namespaces
                $namespacedClasses[] = 'App\\Test\\Fixture\\' . $fixtureClass;
                $namespacedClasses[] = 'Cake\\Test\\Fixture\\' . $fixtureClass;
            }

            $loaded = false;
            foreach ($namespacedClasses as $namespacedClass) {
                if (class_exists($namespacedClass)) {
                    $this->_loaded[$fixtureIndex] = new $namespacedClass();
                    $this->_fixtureMap[$fixtureClass] = $this->_loaded[$fixtureIndex];
                    $loaded = true;
                    break;
                }
            }

            // If modern approach failed, try legacy approach (require file + non-namespaced class)
            if (!$loaded) {
                foreach ($fixturePaths as $path) {
                    if (is_readable($path . DS . $className . 'Fixture.php')) {
                        $fixtureFile = $path . DS . $className . 'Fixture.php';
                        require_once $fixtureFile;
                        $this->_loaded[$fixtureIndex] = new $fixtureClass();
                        $this->_fixtureMap[$fixtureClass] = $this->_loaded[$fixtureIndex];
                        $loaded = true;
                        break;
                    }
                }
            }

            if (!$loaded) {
                $firstPath = str_replace([APP, CAKE_CORE_INCLUDE_PATH, ROOT], '', $fixturePaths[0] . DS . $className . 'Fixture.php');
                throw new UnexpectedValueException(__d('cake_dev', 'Referenced fixture class %s (%s) not found', $fixtureClass, $firstPath));
            }
        }
    }

    /**
     * Runs the drop, create and truncate commands on the fixtures if necessary.
     *
     * @param CakeTestFixture $fixture the fixture object to create
     * @param DboSource|null $db the datasource instance to use
     * @param bool $drop whether drop the fixture if it is already created or not
     * @return void
     */
    protected function _setupTable(
        CakeTestFixture $fixture,
        ?DboSource $db = null,
        bool $drop = true,
    ): void {
        if (!$db) {
            if (!empty($fixture->useDbConfig)) {
                $db = ConnectionManager::getDboSource($fixture->useDbConfig);
            } else {
                $db = $this->_db;
            }
        }
        if (!empty($fixture->created) && in_array($db->configKeyName, $fixture->created)) {
            $fixture->truncate($db);

            return;
        }

        $sources = $db->listSources();
        $table = $db->config['prefix'] . $fixture->table;
        $exists = in_array($table, (array)$sources);

        if ($drop && $exists) {
            $fixture->drop($db);
            $fixture->create($db);
        } elseif (!$exists) {
            $fixture->create($db);
        } else {
            $fixture->created[] = $db->configKeyName;
            $fixture->truncate($db);
        }
    }

    /**
     * Creates the fixtures tables and inserts data on them.
     *
     * @param CakeTestCase $test the test to inspect for fixture loading
     * @return void
     */
    public function load(CakeTestCase $test): void
    {
        if (empty($test->fixtures) || !$test->autoFixtures) {
            return;
        }
        $fixtures = $test->fixtures;

        foreach ($fixtures as $f) {
            if (!empty($this->_loaded[$f])) {
                $fixture = $this->_loaded[$f];
                $db = ConnectionManager::getDboSource($fixture->useDbConfig);
                $this->_setupTable($fixture, $db, $test->dropTables);
                $db->begin();
                $fixture->insert($db);
                $db->commit();
            }
        }
    }

    /**
     * Truncates the fixtures tables
     *
     * @param CakeTestCase $test the test to inspect for fixture unloading
     * @return void
     */
    public function unload(CakeTestCase $test): void
    {
        $fixtures = !empty($test->fixtures) ? $test->fixtures : [];
        foreach (array_reverse($fixtures) as $f) {
            if (isset($this->_loaded[$f])) {
                $fixture = $this->_loaded[$f];
                if (!empty($fixture->created)) {
                    foreach ($fixture->created as $ds) {
                        $db = ConnectionManager::getDboSource($ds);
                        $fixture->truncate($db);
                    }
                }
            }
        }
    }

    /**
     * Creates a single fixture table and loads data into it.
     *
     * @param string $name of the fixture
     * @param DboSource|null $db DataSource instance or leave null to get DboSource from the fixture
     * @param bool $dropTables Whether or not tables should be dropped and re-created.
     * @return void
     * @throws UnexpectedValueException if $name is not a previously loaded class
     */
    public function loadSingle(
        string $name,
        ?DboSource $db = null,
        bool $dropTables = true,
    ): void {
        $name .= 'Fixture';
        if (isset($this->_fixtureMap[$name])) {
            $fixture = $this->_fixtureMap[$name];
            if (!$db) {
                $db = ConnectionManager::getDboSource($fixture->useDbConfig);
            }
            $this->_setupTable($fixture, $db, $dropTables);
            $fixture->insert($db);
        } else {
            throw new UnexpectedValueException(__d('cake_dev', 'Referenced fixture class %s not found', $name));
        }
    }

    /**
     * Drop all fixture tables loaded by this class
     *
     * This will also close the session, as failing to do so will cause
     * fatal errors with database sessions.
     *
     * @return void
     */
    public function shutDown(): void
    {
        if (session_id()) {
            session_write_close();
        }
        foreach ($this->_loaded as $fixture) {
            if (!empty($fixture->created)) {
                foreach ($fixture->created as $ds) {
                    $db = ConnectionManager::getDboSource($ds);
                    $fixture->drop($db);
                }
            }
        }
    }
}
