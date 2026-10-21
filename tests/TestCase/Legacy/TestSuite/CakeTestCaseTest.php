<?php
/**
 * CakeTestCaseTest file
 *
 * Test Case for CakeTestCase class
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
 * @package       Cake.Test.Case.TestSuite
 * @since         CakePHP v 1.2.0.4487
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\TestSuite;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingModelException;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;

/**
 * CakeTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestCaseTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['core.post', 'core.author', 'core.test_plugin_comment'];

    /**
     * test getMockForModel()
     *
     * @return void
     */
    public function testGetMockForModel(): void
    {
        App::build([
            'Model' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS,
            ],
        ], App::RESET);
        $Post = $this->getMockForModel('Post');
        $this->assertEquals('test', $Post->useDbConfig);
        $this->assertInstanceOf('Post', $Post);
        $this->assertNull($Post->save([]));
        $this->assertNull($Post->find('all'));
        $this->assertEquals('posts', $Post->useTable);

        $Post = $this->getMockForModel('Post', ['save']);

        $this->assertNull($Post->save([]));
        $this->assertIsArray($Post->find('all'));
    }

    /**
     * Test getMockForModel on secondary datasources.
     *
     * @return void
     */
    public function testGetMockForModelSecondaryDatasource(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Model/Datasource/Database' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        ConnectionManager::create('test_secondary', [
            'datasource' => 'Database/TestLocalDriver',
            'prefix' => '',
        ]);
        $post = $this->getMockForModel('SecondaryPost', ['save']);
        $this->assertEquals('test_secondary', $post->useDbConfig);
        ConnectionManager::drop('test_secondary');
    }

    /**
     * Test getMockForModel when the model accesses the datasource in the constructor.
     *
     * @return void
     */
    public function testGetMockForModelConstructorDatasource(): void
    {
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
        $post = $this->getMockForModel('ConstructorPost', ['save'], ['ds' => 'test']);
        $this->assertEquals('test', $post->useDbConfig);
    }

    /**
     * test getMockForModel() with plugin models
     *
     * @return void
     */
    public function testGetMockForModelWithPlugin(): void
    {
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
        $this->getMockForModel('TestLegacyPlugin.TestLegacyPluginAppModel');
        $this->getMockForModel('TestLegacyPlugin.TestPluginComment');

        $result = ClassRegistry::init('TestLegacyPlugin.TestPluginComment');
        $this->assertInstanceOf('TestPluginComment', $result);
        $this->assertEquals('test', $result->useDbConfig);

        $TestPluginComment = $this->getMockForModel('TestLegacyPlugin.TestPluginComment', ['save']);

        $this->assertInstanceOf('TestPluginComment', $TestPluginComment);
        $TestPluginComment->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($TestPluginComment->save([]));
        $this->assertFalse($TestPluginComment->save([]));
    }

    /**
     * testGetMockForModelModel
     *
     * @return void
     */
    public function testGetMockForModelModel(): void
    {
        $Mock = $this->getMockForModel('Model', ['save', 'setDataSource'], ['name' => 'Comment']);

        $result = ClassRegistry::init('Comment');
        $this->assertInstanceOf('Model', $result);

        $Mock->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls(true, false);

        $this->assertTrue($Mock->save([]));
        $this->assertFalse($Mock->save([]));
    }

    /**
     * testGetMockForModelDoesNotExist
     *
     * @return void
     */
    public function testGetMockForModelDoesNotExist(): void
    {
        $this->expectException(MissingModelException::class);
        $this->expectExceptionMessage('Model IDoNotExist could not be found');
        $this->getMockForModel('IDoNotExist');
    }
}
