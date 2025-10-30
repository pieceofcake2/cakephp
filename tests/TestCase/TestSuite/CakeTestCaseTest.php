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

namespace Cake\Test\TestCase\TestSuite;

use App\Model\Post;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingModelException;
use Cake\Model\ConnectionManager;
use Cake\Model\Model;
use Cake\Test\Fixture\AssertTagsTestCase;
use Cake\Test\Fixture\FixturizedTestCase;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeFixtureManager;
use Cake\Utility\ClassRegistry;
use TestPlugin\Model\TestPluginComment;

require_once dirname(__DIR__) . DS . 'Model' . DS . 'models.php';

/**
 * Secondary Post stub class.
 */
class SecondaryPost extends Model
{
    /**
     * @var string
     */
    public string|bool|null $useTable = 'posts';

    /**
     * @var string
     */
    public string $useDbConfig = 'secondary';
}

/**
 * ConstructorPost test stub.
 */
class ConstructorPost extends Model
{
    /**
     * @var string
     */
    public string|bool|null $useTable = 'posts';

    public function __construct($id = false, $table = null, $ds = null)
    {
        parent::__construct($id, $table, $ds);
        $this->getDataSource()->cacheMethods = false;
    }
}

/**
 * CakeTestCaseTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestCaseTest extends CakeTestCase
{
    protected ?string $_appNamespace = null;

    /**
     * fixtures property
     *
     * @var array
     */
    public array $fixtures = [
        'core.post',
        'core.author',
        'core.test_plugin_comment',
    ];

    /**
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->_appNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Result);
        Configure::write('App.namespace', $this->_appNamespace);

        parent::tearDown();
    }

    /**
     * testAssertTags
     *
     * @return void
     */
    public function testAssertTagsBasic()
    {
        $test = new AssertTagsTestCase('testAssertTagsQuotes');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * test assertTags works with single and double quotes
     *
     * @return void
     */
    public function testAssertTagsQuoting()
    {
        $input = '<a href="/test.html" class="active">My link</a>';
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a',
        ];
        $this->assertTags($input, $pattern);

        $input = "<a href='/test.html' class='active'>My link</a>";
        $pattern = [
            'a' => ['href' => '/test.html', 'class' => 'active'],
            'My link',
            '/a',
        ];
        $this->assertTags($input, $pattern);

        $input = "<a href='/test.html' class='active'>My link</a>";
        $pattern = [
            'a' => ['href' => 'preg:/.*\.html/', 'class' => 'active'],
            'My link',
            '/a',
        ];
        $this->assertTags($input, $pattern);

        $input = '<span><strong>Text</strong></span>';
        $pattern = [
            '<span',
            '<strong',
            'Text',
            '/strong',
            '/span',
        ];
        $this->assertTags($input, $pattern);

        $input = "<span class='active'><strong>Text</strong></span>";
        $pattern = [
            'span' => ['class'],
            '<strong',
            'Text',
            '/strong',
            '/span',
        ];
        $this->assertTags($input, $pattern);
    }

    /**
     * Test that assertTags runs quickly.
     *
     * @return void
     */
    public function testAssertTagsRuntimeComplexity()
    {
        $pattern = [
            'div' => [
                'attr1' => 'val1',
                'attr2' => 'val2',
                'attr3' => 'val3',
                'attr4' => 'val4',
                'attr5' => 'val5',
                'attr6' => 'val6',
                'attr7' => 'val7',
                'attr8' => 'val8',
            ],
            'My div',
            '/div',
        ];
        $input = '<div attr8="val8" attr6="val6" attr4="val4" attr2="val2"' .
            ' attr1="val1" attr3="val3" attr5="val5" attr7="val7" />' .
            'My div' .
            '</div>';
        $this->assertTags($input, $pattern);
    }

    /**
     * testNumericValuesInExpectationForAssertTags
     *
     * @return void
     */
    public function testNumericValuesInExpectationForAssertTags()
    {
        $test = new AssertTagsTestCase('testNumericValuesInExpectationForAssertTags');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * testBadAssertTags
     *
     * @return void
     */
    public function testBadAssertTags()
    {
        $test = new AssertTagsTestCase('testBadAssertTags');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());

        $test = new AssertTagsTestCase('testBadAssertTags2');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertFalse($result->wasSuccessful());
        $this->assertEquals(1, $result->failureCount());
    }

    /**
     * testLoadFixtures
     *
     * @return void
     */
    public function testLoadFixtures()
    {
        $test = new FixturizedTestCase('testFixturePresent');
        $manager = $this->getMock(CakeFixtureManager::class);
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->never())->method('load');
        $manager->expects($this->never())->method('unload');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(0, $result->failureCount());
    }

    /**
     * testLoadFixturesOnDemand
     *
     * @return void
     */
    public function testLoadFixturesOnDemand()
    {
        $test = new FixturizedTestCase('testFixtureLoadOnDemand');
        $test->autoFixtures = false;
        $manager = $this->getMock(CakeFixtureManager::class);
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->once())->method('loadSingle');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
    }

    /**
     * testLoadFixturesOnDemand
     *
     * @return void
     */
    public function testUnoadFixturesAfterFailure()
    {
        $test = new FixturizedTestCase('testFixtureLoadOnDemand');
        $test->autoFixtures = false;
        $manager = $this->getMock(CakeFixtureManager::class);
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $manager->expects($this->never())->method('unload');
        $manager->expects($this->once())->method('loadSingle');
        $result = $test->run();
        $this->assertEquals(0, $result->errorCount());
    }

    /**
     * testThrowException
     *
     * @return void
     */
    public function testThrowException()
    {
        $test = new FixturizedTestCase('testThrowException');
        $test->autoFixtures = false;
        $manager = $this->getMock(CakeFixtureManager::class);
        $manager->fixturize($test);
        $test->fixtureManager = $manager;
        $result = $test->run();
        $this->assertEquals(1, $result->errorCount());
    }

    /**
     * testSkipIf
     *
     * @return void
     */
    public function testSkipIf()
    {
        $test = new FixturizedTestCase('testSkipIfTrue');
        $result = $test->run();
        $this->assertEquals(1, $result->skippedCount());

        $test = new FixturizedTestCase('testSkipIfFalse');
        $result = $test->run();
        $this->assertEquals(0, $result->skippedCount());
    }

    /**
     * Test that CakeTestCase::setUp() backs up values.
     *
     * @return void
     */
    public function testSetupBackUpValues()
    {
        $this->assertArrayHasKey('debug', $this->_configure);
        $this->assertArrayHasKey('Plugin', $this->_pathRestore);
    }

    /**
     * test assertTextNotEquals()
     *
     * @return void
     */
    public function testAssertTextNotEquals()
    {
        $one = "\r\nOne\rTwooo";
        $two = "\nOne\nTwo";
        $this->assertTextNotEquals($one, $two);
    }

    /**
     * test assertTextEquals()
     *
     * @return void
     */
    public function testAssertTextEquals()
    {
        $one = "\r\nOne\rTwo";
        $two = "\nOne\nTwo";
        $this->assertTextEquals($one, $two);
    }

    /**
     * test assertTextStartsWith()
     *
     * @return void
     */
    public function testAssertTextStartsWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";

        $this->assertStringStartsWith("some\nstring", $stringDirty);
        $this->assertStringStartsNotWith("some\r\nstring\r\nwith", $stringDirty);
        $this->assertStringStartsNotWith("some\nstring\nwith", $stringDirty);

        $this->assertTextStartsWith("some\nstring\nwith", $stringDirty);
        $this->assertTextStartsWith("some\r\nstring\r\nwith", $stringDirty);
    }

    /**
     * test assertTextStartsNotWith()
     *
     * @return void
     */
    public function testAssertTextStartsNotWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertTextStartsNotWith("some\nstring\nwithout", $stringDirty);
    }

    /**
     * test assertTextEndsWith()
     *
     * @return void
     */
    public function testAssertTextEndsWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertTextEndsWith("string\nwith\r\ndifferent\rline endings!", $stringDirty);
        $this->assertTextEndsWith("string\r\nwith\ndifferent\nline endings!", $stringDirty);
    }

    /**
     * test assertTextEndsNotWith()
     *
     * @return void
     */
    public function testAssertTextEndsNotWith()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertStringEndsNotWith("different\nline endings", $stringDirty);
        $this->assertTextEndsNotWith("different\rline endings", $stringDirty);
    }

    /**
     * test assertTextContains()
     *
     * @return void
     */
    public function testAssertTextContains()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertStringContainsString('different', $stringDirty);
        $this->assertStringNotContainsString("different\rline", $stringDirty);
        $this->assertTextContains("different\rline", $stringDirty);
    }

    /**
     * test assertTextNotContains()
     *
     * @return void
     */
    public function testAssertTextNotContains()
    {
        $stringDirty = "some\nstring\r\nwith\rdifferent\nline endings!";
        $this->assertTextNotContains("different\rlines", $stringDirty);
    }

    /**
     * test getMockForModel()
     *
     * @return void
     */
    public function testGetMockForModel()
    {
        App::build([
            'Model' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS,
            ],
        ], App::RESET);
        $Post = $this->getMockForModel('Post');
        $this->assertEquals('test', $Post->useDbConfig);
        $this->assertInstanceOf(Post::class, $Post);
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
    public function testGetMockForModelSecondaryDatasource()
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
            'Model/Datasource/Database' => [
                CORE_TESTS . DS . 'test_app' . DS . 'src' . DS . 'Model' . DS . 'Datasource' . DS . 'Database' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        ConnectionManager::create('test_secondary', [
            'datasource' => 'Database/TestLocalDriver',
            'prefix' => '',
        ]);
        $post = $this->getMockForModel(SecondaryPost::class, ['save']);
        $this->assertEquals('test_secondary', $post->useDbConfig);
        ConnectionManager::drop('test_secondary');
    }

    /**
     * Test getMockForModel when the model accesses the datasource in the constructor.
     *
     * @return void
     */
    public function testGetMockForModelConstructorDatasource()
    {
        $post = $this->getMockForModel(ConstructorPost::class, ['save'], ['ds' => 'test']);
        $this->assertEquals('test', $post->useDbConfig);
    }

    /**
     * test getMockForModel() with plugin models
     *
     * @return void
     */
    public function testGetMockForModelWithPlugin()
    {
        App::build([
            'Plugin' => [
                CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS,
            ],
        ], App::RESET);
        CakePlugin::load('TestPlugin');
        new TestPluginComment();

        $result = ClassRegistry::init('TestPlugin.TestPluginComment');
        $this->assertInstanceOf(TestPluginComment::class, $result);
        $this->assertEquals('test', $result->useDbConfig);

        $testPluginComment = $this->getMockForModel('TestPlugin.TestPluginComment', ['save']);
        $this->assertInstanceOf(TestPluginComment::class, $testPluginComment);
        $testPluginComment->expects($this->exactly(2))
            ->method('save')
            ->willReturnOnConsecutiveCalls(true, false);
        $this->assertTrue($testPluginComment->save([]));
        $this->assertFalse($testPluginComment->save([]));
    }

    /**
     * testGetMockForModelModel
     *
     * @return void
     */
    public function testGetMockForModelModel()
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
    public function testGetMockForModelDoesNotExist()
    {
        $this->expectException(MissingModelException::class);
        $this->expectExceptionMessage('Model IDoNotExist could not be found');
        $this->getMockForModel('IDoNotExist');
    }
}
