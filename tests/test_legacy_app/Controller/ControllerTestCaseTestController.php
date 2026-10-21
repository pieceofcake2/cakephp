<?php
/**
 * ControllerTestCaseTestController file
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
 * @package       Cake.Test.TestApp.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('AppController', 'Controller');

/**
 * ControllerTestCaseTest controller
 *
 * @package       Cake.Test.TestApp.Controller
 */
class ControllerTestCaseTestController extends AppController
{
    /**
     * Uses array
     *
     * @param array
     */
    public $uses = ['TestLegacyPlugin.TestPluginComment'];
}
