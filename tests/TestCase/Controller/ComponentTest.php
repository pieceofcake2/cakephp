<?php
/**
 * ComponentTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Component\EmailComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use TestApp\Controller\Component\AppleComponent;
use TestApp\Controller\Component\BananaComponent;
use TestApp\Controller\Component\OrangeComponent;
use TestApp\Controller\Component\SomethingWithEmailComponent;
use TestApp\Controller\ComponentTestController;

/**
 * ComponentTest class
 *
 * @package       Cake.Test.Case.Controller
 */
class ComponentTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
        $this->_pluginPaths = App::path('plugins');
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS],
        ]);
    }

    /**
     * test accessing inner components.
     *
     * @return void
     */
    public function testInnerComponentConstruction(): void
    {
        $Collection = new ComponentCollection();
        $Component = new AppleComponent($Collection);

        $this->assertInstanceOf(OrangeComponent::class, $Component->Orange, 'class is wrong');
    }

    /**
     * test component loading
     *
     * @return void
     */
    public function testNestedComponentLoading(): void
    {
        $Collection = new ComponentCollection();
        $Apple = new AppleComponent($Collection);

        $this->assertInstanceOf(OrangeComponent::class, $Apple->Orange, 'class is wrong');
        $this->assertInstanceOf(BananaComponent::class, $Apple->Orange->Banana, 'class is wrong');
        $this->assertTrue(empty($Apple->Session));
        $this->assertTrue(empty($Apple->Orange->Session));
    }

    /**
     * test that component components are not enabled in the collection.
     *
     * @return void
     */
    public function testInnerComponentsAreNotEnabled(): void
    {
        $Collection = new ComponentCollection();
        $Apple = $Collection->load('Apple');

        $this->assertInstanceOf(OrangeComponent::class, $Apple->Orange, 'class is wrong');
        $result = $Collection->enabled();
        $this->assertEquals(['Apple'], $result, 'Too many components enabled.');
    }

    /**
     * test a component being used more than once.
     *
     * @return void
     */
    public function testMultipleComponentInitialize(): void
    {
        $Collection = new ComponentCollection();
        $Banana = $Collection->load('Banana');
        $Orange = $Collection->load('Orange');

        $this->assertSame($Banana, $Orange->Banana, 'Should be references');
        $Banana->testField = 'OrangeField';

        $this->assertSame($Banana->testField, $Orange->Banana->testField, 'References are broken');
    }

    /**
     * Test mutually referencing components.
     *
     * @return void
     */
    public function testSomethingReferencingEmailComponent(): void
    {
        $Controller = new ComponentTestController();
        $Controller->components = ['SomethingWithEmail'];
        $Controller->uses = false;
        $Controller->constructClasses();
        $Controller->Components->trigger('initialize', [&$Controller]);
        $Controller->beforeFilter();
        $Controller->Components->trigger('startup', [&$Controller]);

        $this->assertInstanceOf(SomethingWithEmailComponent::class, $Controller->SomethingWithEmail);
        $this->assertInstanceOf(EmailComponent::class, $Controller->SomethingWithEmail->Email);
    }

    /**
     * Test lazy loading of components inside components and both explicit and
     * implicit 'enabled' setting.
     *
     * @return void
     */
    public function testGet(): void
    {
        $Collection = new ComponentCollection();
        $ParamTest = $Collection->load('ParamTest');
        $this->assertTrue($ParamTest->Apple->settings['enabled']);
        $this->assertFalse($ParamTest->Banana->settings['enabled']);
    }
}
