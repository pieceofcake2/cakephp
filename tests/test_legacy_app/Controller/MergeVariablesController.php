<?php
/**
 * Test App MergeVariablesController
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
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 1.2.3
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('MergeVarsAppController', 'Controller');

/**
 * Additional controller for testing
 *
 * @package       Cake.Test.TestApp.Controller
 */
class MergeVariablesController extends MergeVarsAppController
{
    /**
     * uses
     *
     * @var arrays
     */
    public $uses = [];

    /**
     * parent for mergeVars
     *
     * @var string
     */
    protected $_mergeParent = 'MergeVarsAppController';
}
