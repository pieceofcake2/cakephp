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

namespace TestApp\Controller;

use Cake\Controller\Controller;

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
    public $components = ['MergeVar' => ['flag', 'otherFlag', 'redirect' => false]];

    /**
     * helpers
     *
     * @var array
     */
    public $helpers = ['MergeVar' => ['format' => 'html', 'terse']];
}
