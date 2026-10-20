<?php

use TestApp\View\Helper\NamespacedHelper;

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

/**
 * Extended HtmlHelper
 */
class HtmlAliasHelper extends HtmlHelper
{
}

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
        $this->View = $this->getMock('View', [], [null]);
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
    public function testLoad(): void
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
    public function testLazyLoad(): void
    {
        $result = $this->Helpers->Html;
        $this->assertInstanceOf('HtmlHelper', $result);

        $result = $this->Helpers->Form;
        $this->assertInstanceOf('FormHelper', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS]]);
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
    public function testLazyLoadException(): void
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->NotAHelper;
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Helpers->load('Html', ['className' => 'HtmlAlias']);
        $this->assertInstanceOf('HtmlAliasHelper', $result);
        $this->assertInstanceOf('HtmlAliasHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));

        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('HtmlAliasHelper', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load(['TestPlugin']);
        $result = $this->Helpers->load('SomeOther', ['className' => 'TestPlugin.OtherHelper']);
        $this->assertInstanceOf('OtherHelperHelper', $result);
        $this->assertInstanceOf('OtherHelperHelper', $this->Helpers->SomeOther);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
    }

    /**
     * test that the enabled setting disables the helper.
     *
     * @return void
     */
    public function testLoadWithEnabledFalse(): void
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
    public function testLoadMissingHelper(): void
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginHelper(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
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
    public function testUnload(): void
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

    /**
     * Test loading helpers by fully qualified class name and from App.namespace.
     *
     * @return void
     */
    public function testLoadNamespaced(): void
    {
        $result = $this->Helpers->load('Namespaced', ['className' => NamespacedHelper::class]);
        $this->assertInstanceOf(NamespacedHelper::class, $result);
        $this->assertSame($result, $this->Helpers->Namespaced);

        $result = $this->Helpers->load('WithoutSuffix', ['className' => 'TestApp\View\Helper\Namespaced']);
        $this->assertInstanceOf(NamespacedHelper::class, $result);

        $this->assertInstanceOf('HtmlHelper', $this->Helpers->load('Html'));

        Configure::write('App.namespace', 'TestApp');
        $Helpers = new HelperCollection($this->View);
        $this->assertInstanceOf(NamespacedHelper::class, $Helpers->load('Namespaced'));
    }

    /**
     * Test that a fully qualified class name as the name gives the short name as alias
     * like the short name with className.
     *
     * @return void
     */
    public function testLoadNamespacedClassName(): void
    {
        $expected = $this->Helpers->load('Namespaced', ['className' => NamespacedHelper::class, 'foo' => 'bar']);

        foreach ([NamespacedHelper::class, 'TestApp\View\Helper\Namespaced'] as $name) {
            $Helpers = new HelperCollection($this->View);
            $result = $Helpers->load($name, ['foo' => 'bar']);
            $this->assertInstanceOf(NamespacedHelper::class, $result);
            $this->assertSame($result, $Helpers->Namespaced);
            $this->assertSame($this->Helpers->loaded(), $Helpers->loaded());
            $this->assertSame($expected->settings['foo'], $result->settings['foo']);
            $this->assertSame($result, $Helpers->load($name));
            $this->assertTrue($Helpers->enabled($name));
            $Helpers->unload($name);
            $this->assertSame([], $Helpers->loaded());
        }
    }

    /**
     * Test that fully qualified class names as keys of $helpers give the
     * short names as aliases like the short names with className.
     *
     * @return void
     */
    public function testLoadHelpersNamespacedClassName(): void
    {
        $cases = [
            ['Namespaced' => ['className' => NamespacedHelper::class, 'foo' => 'bar']],
            [NamespacedHelper::class => ['foo' => 'bar']],
            ['TestApp\View\Helper\Namespaced' => ['foo' => 'bar']],
        ];
        foreach ($cases as $helpers) {
            $View = new View(null);
            $View->helpers = $helpers;
            $View->loadHelpers();
            $this->assertTrue(property_exists($View, 'Namespaced'));
            $this->assertInstanceOf(NamespacedHelper::class, $View->Namespaced);
            $this->assertSame($View->Namespaced, $View->Helpers->Namespaced);
            $this->assertSame(['Namespaced'], $View->Helpers->loaded());
            $this->assertSame('bar', $View->Namespaced->settings['foo']);
        }

        $Helper = new class ($this->View) extends Helper {
            public $helpers = [NamespacedHelper::class];
        };
        $this->View->expects($this->once())->method('loadHelper')
            ->with(NamespacedHelper::class)
            ->will($this->returnValue($expected = new NamespacedHelper($this->View)));
        $this->assertSame($expected, $Helper->Namespaced);
    }
}
