<?php

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

namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Component\CookieComponent;
use Cake\Controller\ComponentCollection;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\MissingComponentException;
use Cake\TestSuite\CakeTestCase;
use TestApp\Controller\Component\NamespacedComponent;
use TestPlugin\Controller\Component\OtherComponent;

/**
 * Extended CookieComponent
 */
class CookieAliasComponent extends CookieComponent
{
}
class_alias(CookieAliasComponent::class, 'App\\Controller\\Component\\CookieAliasComponent');

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
        $this->assertInstanceOf(CookieComponent::class, $result);
        $this->assertInstanceOf(CookieComponent::class, $this->Components->Cookie);

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
        $this->assertInstanceOf(CookieAliasComponent::class, $result);
        $this->assertInstanceOf(CookieAliasComponent::class, $this->Components->Cookie);
        $this->assertTrue($this->Components->Cookie->settings['somesetting']);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Components->enabled('Cookie'));

        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf(CookieAliasComponent::class, $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]]);
        CakePlugin::load('TestPlugin');
        $result = $this->Components->load('SomeOther', ['className' => 'TestPlugin.Other']);
        $this->assertInstanceOf(OtherComponent::class, $result);
        $this->assertInstanceOf(OtherComponent::class, $this->Components->SomeOther);

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
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestPlugin');
        $result = $this->Components->load('TestPlugin.Other');
        $this->assertInstanceOf(OtherComponent::class, $result, 'Component class is wrong.');
        $this->assertInstanceOf(OtherComponent::class, $this->Components->Other, 'Class is wrong');
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
        $controller = $this->getMock(Controller::class);
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
}
