<?php
/**
 * BehaviorTest file
 *
 * Long description for behavior.test.php
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
 * @package       Cake.Test.Case.Model
 * @since         1.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model;

use Apple;
use Article;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingBehaviorException;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Comment;
use Sample;

App::uses('Apple', 'Model');
App::uses('Article', 'Model');
App::uses('Comment', 'Model');
App::uses('Sample', 'Model');

/**
 * BehaviorCollection class
 *
 * @package       Cake.Test.Case.Model
 */
class BehaviorCollectionTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = [
        'core.apple', 'core.sample', 'core.article', 'core.user', 'core.comment',
        'core.attachment', 'core.tag', 'core.articles_tag', 'core.translate',
        'core.device',
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
            'Model/Behavior' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS . 'Behavior' . DS],
        ]);
    }

    /**
     * Test load() with enabled => false
     *
     * @return void
     */
    public function testLoadDisabled(): void
    {
        $Apple = new Apple();
        $this->assertSame([], $Apple->Behaviors->loaded());

        $Apple->Behaviors->load('Translate', ['enabled' => false]);
        $this->assertTrue($Apple->Behaviors->loaded('Translate'));
        $this->assertFalse($Apple->Behaviors->enabled('Translate'));
    }

    /**
     * Tests loading aliased behaviors
     *
     * @return void
     */
    public function testLoadAlias(): void
    {
        $Apple = new Apple();
        $this->assertSame([], $Apple->Behaviors->loaded());

        $Apple->Behaviors->load('Test', ['className' => 'TestAlias', 'somesetting' => true]);
        $this->assertSame(['Test'], $Apple->Behaviors->loaded());
        $this->assertInstanceOf('TestAliasBehavior', $Apple->Behaviors->Test);
        $this->assertTrue($Apple->Behaviors->Test->settings['Apple']['somesetting']);

        $this->assertEquals('working', $Apple->Behaviors->Test->testMethod($Apple, true));
        $this->assertEquals('working', $Apple->testMethod(true));
        $this->assertEquals('working', $Apple->Behaviors->dispatchMethod($Apple, 'testMethod'));

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load('TestLegacyPlugin');
        $this->assertTrue($Apple->Behaviors->load('SomeOther', ['className' => 'TestLegacyPlugin.TestPluginPersisterOne']));
        $this->assertInstanceOf('TestPluginPersisterOneBehavior', $Apple->Behaviors->SomeOther);

        $result = $Apple->Behaviors->loaded();
        $this->assertEquals(['Test', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
        CakePlugin::unload();
    }

    /**
     * testBehaviorBinding method
     *
     * @return void
     */
    public function testBehaviorBinding(): void
    {
        $Apple = new Apple();
        $this->assertSame([], $Apple->Behaviors->loaded());

        $Apple->Behaviors->load('Test', ['key' => 'value']);
        $this->assertSame(['Test'], $Apple->Behaviors->loaded());
        $this->assertEquals('testbehavior', strtolower($Apple->Behaviors->Test::class));
        $expected = ['beforeFind' => 'on', 'afterFind' => 'off', 'key' => 'value'];
        $this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);
        $this->assertEquals(['priority', 'Apple'], array_keys($Apple->Behaviors->Test->settings));

        $this->assertSame($Apple->Sample->Behaviors->loaded(), []);
        $Apple->Sample->Behaviors->load('Test', ['key2' => 'value2']);
        $this->assertSame($Apple->Sample->Behaviors->loaded(), ['Test']);
        $this->assertEquals(['beforeFind' => 'on', 'afterFind' => 'off', 'key2' => 'value2'], $Apple->Sample->Behaviors->Test->settings['Sample']);

        $this->assertEquals(['priority', 'Apple', 'Sample'], array_keys($Apple->Behaviors->Test->settings));
        $this->assertSame(
            $Apple->Sample->Behaviors->Test->settings,
            $Apple->Behaviors->Test->settings,
        );
        $this->assertNotSame($Apple->Behaviors->Test->settings['Apple'], $Apple->Sample->Behaviors->Test->settings['Sample']);

        $Apple->Behaviors->load('Test', ['key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off']);
        $Apple->Sample->Behaviors->load('Test', ['key' => 'value', 'key3' => 'value3', 'beforeFind' => 'off']);
        $this->assertEquals(['beforeFind' => 'off', 'afterFind' => 'off', 'key' => 'value', 'key2' => 'value2', 'key3' => 'value3'], $Apple->Behaviors->Test->settings['Apple']);
        $this->assertEquals($Apple->Behaviors->Test->settings['Apple'], $Apple->Sample->Behaviors->Test->settings['Sample']);

        $this->assertFalse(isset($Apple->Child->Behaviors->Test));
        $Apple->Child->Behaviors->load('Test', ['key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off']);
        $this->assertEquals($Apple->Child->Behaviors->Test->settings['Child'], $Apple->Sample->Behaviors->Test->settings['Sample']);

        $this->assertFalse(isset($Apple->Parent->Behaviors->Test));
        $Apple->Parent->Behaviors->load('Test', ['key' => 'value', 'key2' => 'value2', 'key3' => 'value3', 'beforeFind' => 'off']);
        $this->assertEquals($Apple->Parent->Behaviors->Test->settings['Parent'], $Apple->Sample->Behaviors->Test->settings['Sample']);

        $Apple->Parent->Behaviors->load('Test', ['key' => 'value', 'key2' => 'value', 'key3' => 'value', 'beforeFind' => 'off']);
        $this->assertNotEquals($Apple->Parent->Behaviors->Test->settings['Parent'], $Apple->Sample->Behaviors->Test->settings['Sample']);

        $Apple->Behaviors->load('Plugin.Test', ['key' => 'new value']);
        $expected = [
            'beforeFind' => 'off', 'afterFind' => 'off', 'key' => 'new value',
            'key2' => 'value2', 'key3' => 'value3',
        ];
        $this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);

        $current = $Apple->Behaviors->Test->settings['Apple'];
        $expected = array_merge($current, ['mangle' => 'trigger mangled']);
        $Apple->Behaviors->load('Test', ['mangle' => 'trigger']);
        $this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);

        $Apple->Behaviors->load('Test');
        $expected = array_merge($current, ['mangle' => 'trigger mangled mangled']);

        $this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);
        $Apple->Behaviors->load('Test', ['mangle' => 'trigger']);
        $expected = array_merge($current, ['mangle' => 'trigger mangled']);
        $this->assertEquals($expected, $Apple->Behaviors->Test->settings['Apple']);
    }

    /**
     * test that attach()/detach() works with plugin.banana
     *
     * @return void
     */
    public function testDetachWithPluginNames(): void
    {
        App::uses('TestBehavior', 'Model/Behavior');
        $this->assertTrue(class_exists('TestBehavior'));
        $Apple = new Apple();
        $Apple->Behaviors->load('Plugin.Test');
        $this->assertTrue(isset($Apple->Behaviors->Test), 'Missing behavior');
        $this->assertEquals(['Test'], $Apple->Behaviors->loaded());

        $Apple->Behaviors->unload('Plugin.Test');
        $this->assertEquals([], $Apple->Behaviors->loaded());

        $Apple->Behaviors->load('Plugin.Test');
        $this->assertTrue(isset($Apple->Behaviors->Test), 'Missing behavior');
        $this->assertEquals(['Test'], $Apple->Behaviors->loaded());

        $Apple->Behaviors->unload('Test');
        $this->assertEquals([], $Apple->Behaviors->loaded());
    }

    /**
     * test that attaching a non existent Behavior triggers a cake error.
     *
     * @return void
     */
    public function testInvalidBehaviorCausingCakeError(): void
    {
        $this->expectException(MissingBehaviorException::class);
        $Apple = new Apple();
        $Apple->Behaviors->load('NoSuchBehavior');
    }

    /**
     * undocumented function
     *
     * @return void
     */
    public function testBindModelCallsInBehaviors(): void
    {
        // hasMany
        $Article = new Article();
        $Article->unbindModel(['hasMany' => ['Comment']]);
        $result = $Article->find('first');
        $this->assertFalse(array_key_exists('Comment', $result));

        $Article->Behaviors->load('Test4');
        $result = $Article->find('first');
        $this->assertTrue(array_key_exists('Comment', $result));

        // belongsTo
        $Article->unbindModel(['belongsTo' => ['User']]);
        $result = $Article->find('first');
        $this->assertFalse(array_key_exists('User', $result));

        $Article->Behaviors->load('Test5');
        $result = $Article->find('first');
        $this->assertTrue(array_key_exists('User', $result));

        // hasAndBelongsToMany
        $Article->unbindModel(['hasAndBelongsToMany' => ['Tag']]);
        $result = $Article->find('first');
        $this->assertFalse(array_key_exists('Tag', $result));

        $Article->Behaviors->load('Test6');
        $result = $Article->find('first');
        $this->assertTrue(array_key_exists('Comment', $result));

        // hasOne
        $Comment = new Comment();
        $Comment->unbindModel(['hasOne' => ['Attachment']]);
        $result = $Comment->find('first');
        $this->assertFalse(array_key_exists('Attachment', $result));

        $Comment->Behaviors->load('Test7');
        $result = $Comment->find('first');
        $this->assertTrue(array_key_exists('Attachment', $result));
    }

    /**
     * Test attach and detaching
     *
     * @return void
     */
    public function testBehaviorAttachAndDetach(): void
    {
        $Sample = new Sample();
        $Sample->actsAs = ['Test3' => ['bar'], 'Test2' => ['foo', 'bar']];
        $Sample->Behaviors->init($Sample->alias, $Sample->actsAs);
        $Sample->Behaviors->load('Test2');
        $Sample->Behaviors->unload('Test3');

        $Sample->Behaviors->trigger('beforeTest', [&$Sample]);

        $this->assertTrue($Sample->Behaviors->enabled('Test2'));
        $this->assertFalse($Sample->Behaviors->enabled('Test3'));
    }

    /**
     * Test that behavior priority
     *
     * @return void
     */
    public function testBehaviorOrderCallbacks(): void
    {
        $model = ClassRegistry::init('Orangutan');
        $model->Behaviors->init('Orangutan', [
            'Second' => ['priority' => 9],
            'Third',
            'First' => ['priority' => 8],
        ]);

        $this->assertEmpty($model->called);

        $model->find('first');
        $expected = [
            'FirstBehavior',
            'SecondBehavior',
            'ThirdBehavior',
        ];
        $this->assertEquals($expected, $model->called);

        $model->called = [];
        $model->Behaviors->load('Third', ['priority' => 1]);

        $model->find('first');
        $expected = [
            'ThirdBehavior',
            'FirstBehavior',
            'SecondBehavior',
        ];
        $this->assertEquals($expected, $model->called);

        $model->called = [];
        $model->Behaviors->load('First');

        $model->find('first');
        $expected = [
            'ThirdBehavior',
            'SecondBehavior',
            'FirstBehavior',
        ];
        $this->assertEquals($expected, $model->called);

        $model->called = [];
        $model->Behaviors->unload('Third');

        $model->find('first');
        $expected = [
            'SecondBehavior',
            'FirstBehavior',
        ];
        $this->assertEquals($expected, $model->called);

        $model->called = [];
        $model->Behaviors->disable('Second');

        $model->find('first');
        $expected = [
            'FirstBehavior',
        ];
        $this->assertEquals($expected, $model->called);

        $model->called = [];
        $model->Behaviors->enable('Second');

        $model->find('first');
        $expected = [
            'SecondBehavior',
            'FirstBehavior',
        ];
        $this->assertEquals($expected, $model->called);
    }
}
