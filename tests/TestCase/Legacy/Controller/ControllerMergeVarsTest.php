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

namespace Cake\Test\TestCase\Legacy\Controller;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use MergePostsController;
use MergeVariablesController;

App::uses('MergePostsController', 'TestMergeVarLegacyPlugin.Controller');
App::uses('MergeVariablesController', 'Controller');

/**
 * Test Case for Controller Merging of Vars.
 *
 * @package       Cake.Test.Case.Controller
 */
class ControllerMergeVarsTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
            'Controller/Component' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestMergeVarLegacyPlugin');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();

        parent::tearDown();
    }

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
     * test merging of vars with plugin
     *
     * @return void
     */
    public function testMergeVarsWithPlugin(): void
    {
        $Controller = new MergePostsController();
        $Controller->components = ['Email' => ['ports' => 'open']];
        $Controller->plugin = 'TestMergeVarLegacyPlugin';
        $Controller->constructClasses();

        $expected = [
            'MergeVar' => ['flag', 'otherFlag', 'redirect' => false],
            'Auth' => ['setting' => 'val', 'otherVal'],
            'Email' => ['ports' => 'open'],
        ];
        $this->assertEquals($expected, $Controller->components, 'Components are unexpected.');

        $expected = [
            'MergeVar' => ['format' => 'html', 'terse'],
            'Js' => null,
        ];
        $this->assertEquals($expected, $Controller->helpers, 'Helpers are unexpected.');

        $Controller = new MergePostsController();
        $Controller->components = [];
        $Controller->plugin = 'TestMergeVarLegacyPlugin';
        $Controller->constructClasses();

        $expected = [
            'MergeVar' => ['flag', 'otherFlag', 'redirect' => false],
            'Auth' => ['setting' => 'val', 'otherVal'],
        ];
        $this->assertEquals($expected, $Controller->components, 'Components are unexpected.');
    }
}
