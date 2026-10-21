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

/**
 * Controller in the src directory of a plugin with the CakePHP 5 layout
 */
class ModernController extends TestNamespacedPluginAppController
{
    public $uses = ['TestNamespacedPlugin.ModernArticle'];

    public $helpers = ['TestNamespacedPlugin.Modern', 'Html'];

    public $layout = 'modern';

    public function index()
    {
        $this->set('test_value', 'It is a variable');
    }

    public function some_method()
    {
        return 25;
    }
}
