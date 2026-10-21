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
use Cake\Core\Configure;
use Cake\Model\ConnectionManager;
use Cake\Model\Datasource\DataSource;
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
    protected $_initialized = false;

    /**
     * Default datasource to use
     *
     * @var DataSource
     */
    protected $_db = null;

    /**
     * Holds the fixture classes that where instantiated
     *
     * @var array
     */
    protected $_loaded = [];

    /**
     * Holds the fixture classes that where instantiated indexed by class name
     *
     * @var array
     */
    protected $_fixtureMap = [];

    /**
     * Test case classes whose fixtures have already been processed, indexed by class name
     *
     * @var array<string, bool>
     */
    protected $_processed = [];

    /**
     * Inspects the test to look for unloaded fixtures and loads them
     *
     * @param CakeTestCase $test the test case to inspect
     * @return void
     */
    public function fixturize($test)
    {
        if (!$this->_initialized) {
            ClassRegistry::config('Model', ['ds' => 'test', 'testing' => true]);
        }
        if (empty($test->fixtures) || !empty($this->_processed[$test::class])) {
            $test->db = $this->_db;

            return;
        }
        $this->_initDb();
        $test->db = $this->_db;
        if (!is_array($test->fixtures)) {
            $test->fixtures = array_map('trim', explode(',', $test->fixtures));
        }
        if (isset($test->fixtures)) {
            $this->_loadFixtures($test->fixtures);
        }

        $this->_processed[$test::class] = true;
    }

    /**
     * Initializes this class with a DataSource object to use as default for all fixtures
     *
     * @return void
     */
    protected function _initDb()
    {
        if ($this->_initialized) {
            return;
        }
        $db = ConnectionManager::getDataSource('test');
        $db->cacheSources = false;
        $this->_db = $db;
        $this->_initialized = true;
    }

    /**
     * Parse the fixture path included in test cases, to get the fixture class name, and the
     * real fixture path including sub-directories
     *
     * @param string $fixturePath the fixture path to parse
     * @return array containing fixture class name and optional additional path
     */
    protected function _parseFixturePath($fixturePath)
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
     * A name with a backslash is a fully qualified class name, e.g. ArticleFixture::class;
     * the Fixture suffix is added only when the name does not end with it.
     *
     * @param array $fixtures the fixture names to load using the notation {type}.{name}, or class names
     * @return void
     * @throws UnexpectedValueException when a referenced fixture does not exist.
     */
    protected function _loadFixtures($fixtures)
    {
        foreach ($fixtures as $fixture) {
            $fixtureFile = null;
            $fixtureIndex = $fixture;
            if (isset($this->_loaded[$fixture])) {
                continue;
            }

            if (str_contains($fixture, '\\')) {
                $fixtureClass = $this->_fixtureClassName($fixture);
                if (!class_exists($fixtureClass)) {
                    throw new UnexpectedValueException(__d('cake_dev', 'Referenced fixture class %s not found', $fixtureClass));
                }
                $this->_loaded[$fixtureIndex] = new $fixtureClass();
                $this->_fixtureMap[substr($fixtureClass, strrpos($fixtureClass, '\\') + 1)] = $this->_loaded[$fixtureIndex];

                continue;
            }

            if (str_starts_with($fixture, 'core.')) {
                $fixture = substr($fixture, strlen('core.'));
                $fixturePaths = [
                    CORE_TESTS . DS . 'Fixture',
                ];
            } elseif (str_starts_with($fixture, 'app.')) {
                $fixturePrefixLess = substr($fixture, strlen('app.'));
                $fixtureParsedPath = $this->_parseFixturePath($fixturePrefixLess);
                $fixture = $fixtureParsedPath['fixture'];
                $fixturePaths = [
                    TESTS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                ];
            } elseif (str_starts_with($fixture, 'plugin.')) {
                $explodedFixture = explode('.', $fixture, 3);
                $pluginName = $explodedFixture[1];
                $fixtureParsedPath = $this->_parseFixturePath($explodedFixture[2]);
                $fixture = $fixtureParsedPath['fixture'];
                $fixturePaths = [
                    CakePlugin::path(Inflector::camelize($pluginName)) . 'tests' . DS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                    CakePlugin::path(Inflector::camelize($pluginName)) . 'Test' . DS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                    TESTS . 'Fixture' . $fixtureParsedPath['additionalPath'],
                ];
            } else {
                $fixturePaths = [
                    TESTS . 'Fixture',
                    CORE_TESTS . DS . 'Fixture',
                ];
            }

            $className = Inflector::camelize($fixture);
            foreach ($this->_fixtureNamespaces($fixtureIndex) as $namespace) {
                $fixtureClass = $namespace . '\\Test\\Fixture\\' . $className . 'Fixture';
                if (class_exists($fixtureClass)) {
                    $this->_loaded[$fixtureIndex] = new $fixtureClass();
                    $this->_fixtureMap[$className . 'Fixture'] = $this->_loaded[$fixtureIndex];

                    continue 2;
                }
            }

            $loaded = false;
            foreach ($fixturePaths as $path) {
                $className = Inflector::camelize($fixture);
                if (is_readable($path . DS . $className . 'Fixture.php')) {
                    $fixtureFile = $path . DS . $className . 'Fixture.php';
                    require_once $fixtureFile;
                    $fixtureClass = $className . 'Fixture';
                    $this->_loaded[$fixtureIndex] = new $fixtureClass();
                    $this->_fixtureMap[$fixtureClass] = $this->_loaded[$fixtureIndex];
                    $loaded = true;
                    break;
                }
            }

            if (!$loaded) {
                $firstPath = str_replace([APP, CAKE_CORE_INCLUDE_PATH, ROOT], '', $fixturePaths[0] . DS . $className . 'Fixture.php');
                throw new UnexpectedValueException(__d('cake_dev', 'Referenced fixture class %s (%s) not found', $className, $firstPath));
            }
        }
    }

    /**
     * Returns the class name of a fixture given by its fully qualified class name,
     * with the Fixture suffix added when the name does not end with it.
     *
     * @param string $name The class name, e.g. ArticleFixture::class or 'App\Test\Fixture\Article'
     * @return string
     */
    protected function _fixtureClassName($name)
    {
        $name = ltrim($name, '\\');
        if (!str_ends_with($name, 'Fixture')) {
            $name .= 'Fixture';
        }

        return $name;
    }

    /**
     * Returns the namespaces to look for a namespaced fixture class in, e.g. Cake for core.article.
     *
     * @param string $fixture The fixture name, e.g. core.article, app.article or plugin.debug_kit.article
     * @return array<string>
     */
    protected function _fixtureNamespaces($fixture)
    {
        $appNamespace = Configure::read('App.namespace');
        $appNamespaces = $appNamespace ? [trim($appNamespace, '\\')] : [];
        if (str_starts_with($fixture, 'core.')) {
            return ['Cake'];
        }
        if (str_starts_with($fixture, 'app.')) {
            return $appNamespaces;
        }
        if (str_starts_with($fixture, 'plugin.')) {
            return [Inflector::camelize(explode('.', $fixture, 3)[1])];
        }

        return array_merge($appNamespaces, ['Cake']);
    }

    /**
     * Runs the drop, create and truncate commands on the fixtures if necessary.
     *
     * @param CakeTestFixture $fixture the fixture object to create
     * @param DataSource $db the datasource instance to use
     * @param bool $drop whether drop the fixture if it is already created or not
     * @return void
     */
    protected function _setupTable($fixture, $db = null, $drop = true)
    {
        if (!$db) {
            if (!empty($fixture->useDbConfig)) {
                $db = ConnectionManager::getDataSource($fixture->useDbConfig);
            } else {
                $db = $this->_db;
            }
        }
        if (!empty($fixture->created) && in_array($db->configKeyName, $fixture->created)) {
            $fixture->truncate($db);

            return;
        }

        $sources = (array)$db->listSources();
        $table = $db->config['prefix'] . $fixture->table;
        $exists = in_array($table, $sources);

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
    public function load(CakeTestCase $test)
    {
        if (empty($test->fixtures)) {
            return;
        }
        $fixtures = $test->fixtures;
        if (!$test->autoFixtures) {
            return;
        }

        foreach ($fixtures as $f) {
            if (!empty($this->_loaded[$f])) {
                $fixture = $this->_loaded[$f];
                $db = ConnectionManager::getDataSource($fixture->useDbConfig);
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
    public function unload(CakeTestCase $test)
    {
        $fixtures = !empty($test->fixtures) ? $test->fixtures : [];
        foreach (array_reverse($fixtures) as $f) {
            if (isset($this->_loaded[$f])) {
                $fixture = $this->_loaded[$f];
                if (!empty($fixture->created)) {
                    foreach ($fixture->created as $ds) {
                        $db = ConnectionManager::getDataSource($ds);
                        $fixture->truncate($db);
                    }
                }
            }
        }
    }

    /**
     * Creates a single fixture table and loads data into it.
     *
     * The fixture is given by its name without the Fixture suffix, e.g. Article, or by
     * its fully qualified class name, e.g. ArticleFixture::class.
     *
     * @param string $name of the fixture
     * @param DataSource $db DataSource instance or leave null to get DataSource from the fixture
     * @param bool $dropTables Whether or not tables should be dropped and re-created.
     * @return void
     * @throws UnexpectedValueException if $name is not a previously loaded class
     */
    public function loadSingle($name, $db = null, $dropTables = true)
    {
        if (str_contains($name, '\\')) {
            $name = $this->_fixtureClassName($name);
            $name = substr($name, strrpos($name, '\\') + 1);
        } else {
            $name .= 'Fixture';
        }
        if (isset($this->_fixtureMap[$name])) {
            $fixture = $this->_fixtureMap[$name];
            if (!$db) {
                $db = ConnectionManager::getDataSource($fixture->useDbConfig);
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
    public function shutDown()
    {
        if (session_id()) {
            session_write_close();
        }
        foreach ($this->_loaded as $fixture) {
            if (!empty($fixture->created)) {
                foreach ($fixture->created as $ds) {
                    $db = ConnectionManager::getDataSource($ds);
                    $fixture->drop($db);
                }
            }
        }
    }
}

class_alias(CakeFixtureManager::class, 'CakeFixtureManager');
