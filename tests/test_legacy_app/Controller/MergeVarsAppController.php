<?php
/**
 * Test App MergeVarsAppController
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

/**
 * Test case AppController
 *
 * @package       Cake.Test.TestApp.Controller
 */
class MergeVarsAppController extends Controller
{
    /**
     * components
     *
     * @var array
     */
    public $components = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['MergeVar' => ['format' => 'html', 'terse']];
}
