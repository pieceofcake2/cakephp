<?php
/**
 * Controller Merge vars Test file
 *
 * Isolated from the Controller and Component test as to not pollute their AppController class
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
 * @since         CakePHP(tm) v 1.2.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\View\View;
use TestApp\Controller\AppController as NamespacedAppController;
use TestApp\Controller\MergePostsController;
use TestApp\Controller\MergeVariablesController;
use TestApp\View\Helper\NamespacedHelper;

/**
 * Test Case for Controller Merging of Vars.
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerMergeVarsTest extends CakeTestCase
{
    /**
     * test that component settings are not duplicated when merging component settings
     *
     * @return void
     */
    public function testComponentParamMergingNoDuplication(): void
    {
        $Controller = new MergeVariablesController();
        $Controller->constructClasses();

        $expected = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];
        $this->assertEquals($expected, $Controller->components, 'Duplication of settings occurred. %s');
    }

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');
    }

    /**
     * test component merges with redeclared components
     *
     * @return void
     */
    public function testComponentMergingWithRedeclarations(): void
    {
        $controller = new MergeVariablesController();
        $controller->components['MergeVar'] = ['remote', 'redirect' => true];
        $controller->constructClasses();

        $expected = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => true, 'remote']];
        $this->assertEquals($expected, $controller->components, 'Merging of settings is wrong. %s');
    }

    /**
     * test merging of helpers array, ensure no duplication occurs
     *
     * @return void
     */
    public function testHelperSettingMergingNoDuplication(): void
    {
        $Controller = new MergeVariablesController();
        $Controller->constructClasses();

        $expected = ['MergeVar' => ['format' => 'html', 'terse']];
        $this->assertEquals($expected, $Controller->helpers, 'Duplication of settings occurred. %s');
    }

    /**
     * Test that helpers declared in appcontroller come before those in the subclass
     * orderwise
     *
     * @return void
     */
    public function testHelperOrderPrecedence(): void
    {
        $Controller = new MergeVariablesController();
        $Controller->helpers = ['Custom', 'Foo' => ['something']];
        $Controller->constructClasses();

        $expected = [
            'MergeVar' => ['format' => 'html', 'terse'],
            'Custom' => null,
            'Foo' => ['something'],
        ];
        $this->assertSame($expected, $Controller->helpers, 'Order is incorrect.');
    }

    /**
     * test merging of vars with plugin
     *
     * @return void
     */
    public function testMergeVarsWithPlugin(): void
    {
        $controller = new MergePostsController();
        $controller->components = ['Email' => ['ports' => 'open']];
        $controller->plugin = 'TestMergeVarPlugin';
        $controller->constructClasses();

        $expected = [
            'MergeVar' => ['flag', 'otherFlag', 'redirect' => false],
            'Auth' => ['setting' => 'val', 'otherVal'],
            'Email' => ['ports' => 'open'],
        ];
        $this->assertEquals($expected, $controller->components, 'Components are unexpected.');

        $expected = [
            'MergeVar' => ['format' => 'html', 'terse'],
            'Js' => null,
        ];
        $this->assertEquals($expected, $controller->helpers, 'Helpers are unexpected.');

        $controller = new MergePostsController();
        $controller->components = [];
        $controller->plugin = 'TestMergeVarPlugin';
        $controller->constructClasses();

        $expected = [
            'MergeVar' => ['flag', 'otherFlag', 'redirect' => false],
            'Auth' => ['setting' => 'val', 'otherVal'],
        ];
        $this->assertEquals($expected, $controller->components, 'Components are unexpected.');
    }

    /**
     * Ensure that _mergeControllerVars is not being greedy and merging with
     * AppController when you make an instance of Controller
     *
     * @return void
     */
    public function testMergeVarsNotGreedy(): void
    {
        $Controller = new Controller();
        $Controller->components = [];
        $Controller->uses = [];
        $Controller->constructClasses();

        $this->assertFalse(isset($Controller->Session));
    }

    /**
     * Ensure that $modelClass is correct even when Controller::$uses
     * has been iterated, eg: by a Component, or event handlers.
     *
     * @return void
     */
    public function testMergeVarsModelClass(): void
    {
        $Controller = new MergeVariablescontroller();
        $Controller->uses = ['Test', 'TestAlias'];
        $Controller->constructClasses();
        $this->assertEquals($Controller->uses[0], $Controller->modelClass);
    }

    /**
     * Test that a short name in the AppController and a fully qualified class name in a controller
     * are merged into one entry.
     *
     * @return void
     */
    public function testMergeVarsNamespacedClassName(): void
    {
        $Controller = new class extends NamespacedAppController {
            public $uses = [];

            public $helpers = [NamespacedHelper::class => ['b' => 2]];
        };
        $Controller->constructClasses();
        $expected = ['Namespaced' => ['b' => 2, 'className' => NamespacedHelper::class]];
        $this->assertSame($expected, $Controller->helpers);

        $View = new View($Controller);
        $View->loadHelpers();
        $this->assertSame(['Namespaced'], $View->Helpers->loaded());
        $this->assertSame(2, $View->Namespaced->settings['b']);
    }
}
