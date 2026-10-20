<?php
/**
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace MergeVarPlugin\Controller;

use TestApp\Controller\MergeVarsAppController;

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
    protected $_mergeParent = MergeVarsAppController::class;
}
