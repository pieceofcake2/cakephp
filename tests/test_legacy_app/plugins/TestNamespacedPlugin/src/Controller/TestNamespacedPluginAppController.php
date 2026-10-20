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

namespace TestNamespacedPlugin\Controller;

use App;
use AppController;

App::uses('AppController', 'Controller');

/**
 * AppController of a plugin with the CakePHP 5 layout in an application with the CakePHP 2 layout
 */
class TestNamespacedPluginAppController extends AppController
{
    public $components = ['TestNamespacedPlugin.Modern'];
}
