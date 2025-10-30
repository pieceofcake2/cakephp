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

use Cake\Controller\Component;
use Cake\Controller\Controller;
use Cake\TestSuite\CakeTestCase;

/**
 * Test case AppController
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarsAppController extends Controller
{
    /**
     * components
     *
     * @var array
     */
    public array $components = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];

    /**
     * helpers
     *
     * @var array
     */
    public array $helpers = ['MergeVar' => ['format' => 'html', 'terse']];
}
class_alias(MergeVarsAppController::class, 'App\\Controller\\MergeVarsAppController');

/**
 * MergeVar Component
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarComponent extends Component
{
}
class_alias(MergeVarComponent::class, 'App\\Controller\\Component\\MergeVarComponent');
class_alias(MergeVarComponent::class, 'MergeVarPlugin\\Controller\\Component\\MergeVarComponent');

/**
 * Additional controller for testing
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVariablesController extends MergeVarsAppController
{
    /**
     * uses
     *
     * @var array|bool|null
     */
    public array|bool|null $uses = [];

    /**
     * parent for mergeVars
     *
     * @var string
     */
    protected string $_mergeParent = 'MergeVarsAppController';
}
class_alias(MergeVariablesController::class, 'App\\Controller\\MergeVariablesController');

/**
 * MergeVarPlugin App Controller
 *
 * @package       Cake.Test.Case.Controller
 */
class MergeVarPluginAppController extends MergeVarsAppController
{
    /**
     * components
     *
     * @var array
     */
    public array $components = ['Auth' => ['setting' => 'val', 'otherVal']];

    /**
     * helpers
     *
     * @var array
     */
    public array $helpers = ['Js'];

    /**
     * parent for mergeVars
     *
     * @var string
     */
    protected string $_mergeParent = 'MergeVarsAppController';
}
class_alias(MergeVarPluginAppController::class, 'App\\Controller\\MergeVarPluginAppController');
class_alias(MergeVarPluginAppController::class, 'MergeVarPlugin\\Controller\\MergeVarPluginAppController');

/**
 * MergePostsController
 *
 * @package       Cake.Test.Case.Controller
 */
class MergePostsController extends MergeVarPluginAppController
{
    /**
     * uses
     *
     * @var array|bool|null
     */
    public array|bool|null $uses = [];
}
class_alias(MergePostsController::class, 'App\\Controller\\MergePostsController');

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
    public function testComponentParamMergingNoDuplication()
    {
        $Controller = new MergeVariablesController();
        $Controller->constructClasses();

        $expected = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];
        $this->assertEquals($expected, $Controller->components, 'Duplication of settings occurred. %s');
    }

    /**
     * test component merges with redeclared components
     *
     * @return void
     */
    public function testComponentMergingWithRedeclarations()
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
    public function testHelperSettingMergingNoDuplication()
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
    public function testHelperOrderPrecedence()
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
    public function testMergeVarsWithPlugin()
    {
        $controller = new MergePostsController();
        $controller->components = ['Email' => ['ports' => 'open']];
        $controller->plugin = 'MergeVarPlugin';
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
        $controller->plugin = 'MergeVarPlugin';
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
    public function testMergeVarsNotGreedy()
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
    public function testMergeVarsModelClass()
    {
        $Controller = new MergeVariablescontroller();
        $Controller->uses = ['Test', 'TestAlias'];
        $Controller->constructClasses();
        $this->assertEquals($Controller->uses[0], $Controller->modelClass);
    }
}
