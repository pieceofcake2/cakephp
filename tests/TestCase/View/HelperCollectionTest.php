<?php
/**
 * HelperCollectionTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\View;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingHelperException;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\HtmlHelper;
use Cake\View\HelperCollection;
use Cake\View\View;
use OtherHelperHelper;

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends HtmlHelper
{
}
class_alias(HtmlAliasHelper::class, 'App\\View\\Helper\\HtmlAliasHelper');

/**
 * HelperCollectionTest
 *
 * @package       Cake.Test.Case.View
 */
class HelperCollectionTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = $this->getMock(View::class, [], [null]);
        $this->Helpers = new HelperCollection($this->View);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        unset($this->Helpers, $this->View);

        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoad()
    {
        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('HtmlHelper', $result);
        $this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));
    }

    /**
     * test lazy loading of helpers
     *
     * @return void
     */
    public function testLazyLoad()
    {
        $result = $this->Helpers->Html;
        $this->assertInstanceOf('HtmlHelper', $result);

        $result = $this->Helpers->Form;
        $this->assertInstanceOf('FormHelper', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]]);
        $this->View->plugin = 'TestPlugin';
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->OtherHelper;
        $this->assertInstanceOf('OtherHelperHelper', $result);
    }

    /**
     * test lazy loading of helpers
     *
     * @return void
     */
    public function testLazyLoadException()
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->NotAHelper;
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias()
    {
        $result = $this->Helpers->load('Html', ['className' => 'HtmlAlias']);
        $this->assertInstanceOf(HtmlAliasHelper::class, $result);
        $this->assertInstanceOf(HtmlAliasHelper::class, $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));

        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf(HtmlAliasHelper::class, $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]]);
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->load('SomeOther', ['className' => 'TestPlugin.OtherHelper']);
        $this->assertInstanceOf(OtherHelperHelper::class, $result);
        $this->assertInstanceOf(OtherHelperHelper::class, $this->Helpers->SomeOther);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
    }

    /**
     * test that the enabled setting disables the helper.
     *
     * @return void
     */
    public function testLoadWithEnabledFalse()
    {
        $result = $this->Helpers->load('Html', ['enabled' => false]);
        $this->assertInstanceOf('HtmlHelper', $result);
        $this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

        $this->assertFalse($this->Helpers->enabled('Html'), 'Html should be disabled');
    }

    /**
     * test missinghelper exception
     *
     * @return void
     */
    public function testLoadMissingHelper()
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginHelper()
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->load('TestPlugin.OtherHelper');
        $this->assertInstanceOf('OtherHelperHelper', $result, 'Helper class is wrong.');
        $this->assertInstanceOf('OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

        App::build();
    }

    /**
     * test unload()
     *
     * @return void
     */
    public function testUnload()
    {
        $this->Helpers->load('Form');
        $this->Helpers->load('Html');

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Form', 'Html'], $result, 'loaded helpers is wrong');

        $this->Helpers->unload('Html');
        $this->assertNotContains('Html', $this->Helpers->loaded());
        $this->assertContains('Form', $this->Helpers->loaded());

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Form'], $result, 'loaded helpers is wrong');
    }
}
