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

namespace Cake\Test\TestCase\Legacy\Controller;

use AppleComponent;
use Cake\Controller\ComponentCollection;
use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\TestSuite\CakeTestCase;

App::uses('AppleComponent', 'Controller/Component');

/**
 * ComponentTestController class
 *
 * @package       Cake.Test.Case.Controller
 */
class ComponentTestController extends Controller
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];
}

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
        $this->_pluginPaths = App::path('plugins');
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
            'Controller/Component' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS],
        ]);
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

        $this->assertInstanceOf('OrangeComponent', $Apple->Orange, 'class is wrong');
        $this->assertInstanceOf('BananaComponent', $Apple->Orange->Banana, 'class is wrong');
        $this->assertTrue(empty($Apple->Session));
        $this->assertTrue(empty($Apple->Orange->Session));
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

        $this->assertInstanceOf('SomethingWithEmailComponent', $Controller->SomethingWithEmail);
        $this->assertInstanceOf('EmailComponent', $Controller->SomethingWithEmail->Email);
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
