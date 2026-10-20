<?php
/**
 * Test App TestMergeVarLegacyPluginAppController
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
 * @package       Cake.Test.TestApp.Plugin.TestMergeVarLegacyPlugin.Controller
 * @since         CakePHP(tm) v 1.2.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('MergeVarsAppController', 'Controller');

/**
 * TestMergeVarLegacyPlugin App Controller
 *
 * @package       Cake.Test.TestApp.Plugin.TestMergeVarLegacyPlugin.Controller
 */
class TestMergeVarLegacyPluginAppController extends MergeVarsAppController
{
    /**
     * components
     *
     * @var array
     */
    public $components = ['Auth' => ['setting' => 'val', 'otherVal']];

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['Js'];

    /**
     * parent for mergeVars
     *
     * @var string
     */
    protected $_mergeParent = 'MergeVarsAppController';
}
