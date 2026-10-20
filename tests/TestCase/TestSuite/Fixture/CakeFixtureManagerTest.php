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
use Cake\Model\ConnectionManager;
use Cake\Test\Fixture\UuidFixture;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeFixtureManager;
use ReflectionClass;
use ReflectionMethod;
use ReflectionProperty;
use TestApp\Test\Fixture\NamespacedArticleFixture;
use TestPluginTwo\Test\Fixture\NamespacedCommentFixture;

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
        unset($this->fixtureManager);

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
        $loadedProperty->setAccessible(true);
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
        $fixtureMapProperty->setAccessible(true);
        $fixtureMapProperty->setValue($fixtureManager, ['UuidFixture' => $MockFixture]);

        $dboMethods = array_diff(get_class_methods('DboSource'), ['enabled']);
        $db = $this->getMock('DboSource', $dboMethods);
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
        CakePlugin::load('TestPluginTwo');

        $loadFixtures = new ReflectionMethod($this->fixtureManager, '_loadFixtures');
        $loadFixtures->setAccessible(true);
        $loadFixtures->invoke($this->fixtureManager, ['app.namespaced_article', 'namespaced_article', 'plugin.test_plugin_two.namespaced_comment']);
        $loaded = new ReflectionProperty($this->fixtureManager, '_loaded');
        $loaded->setAccessible(true);
        $fixtures = $loaded->getValue($this->fixtureManager);

        $this->assertInstanceOf(NamespacedArticleFixture::class, $fixtures['app.namespaced_article']);
        $this->assertInstanceOf(NamespacedArticleFixture::class, $fixtures['namespaced_article']);
        $this->assertInstanceOf(NamespacedCommentFixture::class, $fixtures['plugin.test_plugin_two.namespaced_comment']);

        // The name and the table come from the class name without the namespace
        $this->assertSame('NamespacedArticle', $fixtures['app.namespaced_article']->name);
        $this->assertSame('namespaced_articles', $fixtures['app.namespaced_article']->table);
        $this->assertSame('namespaced_comments', $fixtures['plugin.test_plugin_two.namespaced_comment']->table);
    }
}
