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

namespace TestNamespacedPlugin\Console\Command;

use AppShell;

/**
 * Shell in the src directory of a plugin with the CakePHP 5 layout
 */
class ModernShell extends AppShell
{
    public $tasks = ['TestNamespacedPlugin.Modern'];

    /**
     * main method
     *
     * @return void
     */
    public function main()
    {
        $this->out('This is the main method called from TestNamespacedPlugin.ModernShell');
    }
}
