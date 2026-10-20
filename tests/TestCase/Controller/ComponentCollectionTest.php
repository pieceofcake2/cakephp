<?php

use TestApp\Controller\Component\NamespacedComponent;

/**
 * ComponentCollectionTest file
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
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Extended CookieComponent
 */
class CookieAliasComponent extends CookieComponent
{
}

class ComponentCollectionTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Components = new ComponentCollection();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Components);

        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoad(): void
    {
        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf('CookieComponent', $result);
        $this->assertInstanceOf('CookieComponent', $this->Components->Cookie);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Components->enabled('Cookie'));

        $result = $this->Components->load('Cookie');
        $this->assertSame($result, $this->Components->Cookie);
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Components->load('Cookie', ['className' => 'CookieAlias', 'somesetting' => true]);
        $this->assertInstanceOf('CookieAliasComponent', $result);
        $this->assertInstanceOf('CookieAliasComponent', $this->Components->Cookie);
        $this->assertTrue($this->Components->Cookie->settings['somesetting']);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Components->enabled('Cookie'));

        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf('CookieAliasComponent', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load('TestPlugin');
        $result = $this->Components->load('SomeOther', ['className' => 'TestPlugin.Other']);
        $this->assertInstanceOf('OtherComponent', $result);
        $this->assertInstanceOf('OtherComponent', $this->Components->SomeOther);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
        CakePlugin::unload();
    }

    /**
     * test load and enable = false
     *
     * @return void
     */
    public function testLoadWithEnableFalse(): void
    {
        $result = $this->Components->load('Cookie', ['enabled' => false]);
        $this->assertInstanceOf('CookieComponent', $result);
        $this->assertInstanceOf('CookieComponent', $this->Components->Cookie);

        $this->assertFalse($this->Components->enabled('Cookie'), 'Cookie should be disabled');
    }

    /**
     * test missingcomponent exception
     *
     * @return void
     */
    public function testLoadMissingComponent(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->Components->load('ThisComponentShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin component.
     *
     * @return void
     */
    public function testLoadPluginComponent(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $result = $this->Components->load('TestPlugin.Other');
        $this->assertInstanceOf('OtherComponent', $result, 'Component class is wrong.');
        $this->assertInstanceOf('OtherComponent', $this->Components->Other, 'Class is wrong');
        App::build();
        CakePlugin::unload();
    }

    /**
     * test unload()
     *
     * @return void
     */
    public function testUnload(): void
    {
        $this->Components->load('Cookie');
        $this->Components->load('Security');

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie', 'Security'], $result, 'loaded components is wrong');

        $this->Components->unload('Cookie');
        $this->assertFalse(isset($this->Components->Cookie));
        $this->assertTrue(isset($this->Components->Security));

        $result = $this->Components->loaded();
        $this->assertEquals(['Security'], $result, 'loaded components is wrong');

        $result = $this->Components->enabled();
        $this->assertEquals(['Security'], $result, 'enabled components is wrong');
    }

    /**
     * test getting the controller out of the collection
     *
     * @return void
     */
    public function testGetController(): void
    {
        $controller = $this->getMock('Controller');
        $controller->components = ['Security'];
        $this->Components->init($controller);
        $result = $this->Components->getController();

        $this->assertSame($controller, $result);
    }

    /**
     * Test loading components by fully qualified class name and from App.namespace.
     *
     * @return void
     */
    public function testLoadNamespaced(): void
    {
        $result = $this->Components->load('Namespaced', ['className' => NamespacedComponent::class]);
        $this->assertInstanceOf(NamespacedComponent::class, $result);
        $this->assertSame($result, $this->Components->Namespaced);

        $result = $this->Components->load('Legacy', ['className' => 'TestApp\Controller\Component\Namespaced']);
        $this->assertInstanceOf(NamespacedComponent::class, $result);

        $this->assertInstanceOf('CookieComponent', $this->Components->load('Cookie'));

        Configure::write('App.namespace', 'TestApp');
        $Components = new ComponentCollection();
        $this->assertInstanceOf(NamespacedComponent::class, $Components->load('Namespaced'));
    }

    /**
     * Test that a fully qualified class name as the name gives the short name as alias
     * like the short name with className.
     *
     * @return void
     */
    public function testLoadNamespacedClassName(): void
    {
        $expected = $this->Components->load('Namespaced', ['className' => NamespacedComponent::class, 'foo' => 'bar']);

        foreach ([NamespacedComponent::class, 'TestApp\Controller\Component\Namespaced'] as $name) {
            $Components = new ComponentCollection();
            $result = $Components->load($name, ['foo' => 'bar']);
            $this->assertInstanceOf(NamespacedComponent::class, $result);
            $this->assertSame($result, $Components->Namespaced);
            $this->assertSame($this->Components->loaded(), $Components->loaded());
            $this->assertSame($expected->settings['foo'], $result->settings['foo']);
            $this->assertSame($result, $Components->load($name));
            $this->assertTrue($Components->enabled($name));
            $this->assertTrue($Components->loaded($name));

            $Components->disable($name);
            $this->assertFalse($Components->enabled('Namespaced'));
            $Components->enable($name);
            $this->assertTrue($Components->enabled('Namespaced'));
            $Components->unload($name);
            $this->assertSame([], $Components->loaded());
        }

        $Components = new ComponentCollection();
        $result = $Components->load(NamespacedComponent::class, ['className' => 'Cookie']);
        $this->assertInstanceOf('CookieComponent', $result);
        $this->assertSame(['Namespaced'], $Components->loaded());
    }

    /**
     * Test that fully qualified class names as keys of $components give the
     * short names as aliases like the short names with className.
     *
     * @return void
     */
    public function testInitNamespacedClassName(): void
    {
        $expected = [
            'Namespaced' => ['class' => 'Namespaced', 'settings' => ['className' => NamespacedComponent::class]],
            'Cookie' => ['class' => 'Cookie', 'settings' => []],
        ];
        $this->assertSame($expected, ComponentCollection::normalizeObjectArray([
            'Namespaced' => ['className' => NamespacedComponent::class],
            'Cookie',
        ]));

        $expected = [
            'Namespaced' => ['class' => NamespacedComponent::class, 'settings' => ['foo' => 'bar']],
            'Cookie' => ['class' => 'Cookie', 'settings' => []],
        ];
        $this->assertSame($expected, ComponentCollection::normalizeObjectArray([
            NamespacedComponent::class => ['foo' => 'bar'],
            'Cookie',
        ]));

        $cases = [
            ['Namespaced' => ['className' => NamespacedComponent::class, 'foo' => 'bar']],
            [NamespacedComponent::class => ['foo' => 'bar']],
            ['TestApp\Controller\Component\Namespaced' => ['foo' => 'bar']],
        ];
        foreach ($cases as $components) {
            $Controller = new Controller();
            $Controller->components = $components;
            $Components = new ComponentCollection();
            $Components->init($Controller);
            $this->assertInstanceOf(NamespacedComponent::class, $Controller->Namespaced);
            $this->assertSame($Controller->Namespaced, $Components->Namespaced);
            $this->assertSame(['Namespaced'], $Components->loaded());
            $this->assertSame('bar', $Controller->Namespaced->settings['foo']);
        }

        $Component = new class ($this->Components) extends Component {
            public $components = [NamespacedComponent::class];
        };
        $this->assertInstanceOf(NamespacedComponent::class, $Component->Namespaced);
    }

    /**
     * Test that the missing class of a fully qualified class name has the suffix once.
     *
     * @return void
     */
    public function testLoadMissingNamespacedClassName(): void
    {
        $cases = [
            'NoSuch' => 'NoSuchComponent',
            'TestApp\Controller\Component\Missing' => 'TestApp\Controller\Component\MissingComponent',
            'TestApp\Controller\Component\MissingComponent' => 'TestApp\Controller\Component\MissingComponent',
            '\TestApp\Controller\Component\MissingComponent' => 'TestApp\Controller\Component\MissingComponent',
        ];
        foreach ($cases as $name => $expected) {
            $Collection = new ComponentCollection();
            try {
                $Collection->load($name);
                $this->fail('No exception for ' . $name);
            } catch (MissingComponentException $e) {
                $this->assertSame($expected, $e->getAttributes()['class']);
            }
        }
    }
}
