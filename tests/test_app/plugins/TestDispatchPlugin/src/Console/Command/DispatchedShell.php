<?php
/**
 * DispatchedShell file
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
 * @package       Cake.Test.TestApp.Plugin.TestDispatchPlugin.Console.Command
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace TestDispatchPlugin\Console\Command;

use Cake\Console\Shell;

/**
 * A shell that writes nothing and returns what it was called with, for the tests of
 * ShellDispatcher::dispatch() and Shell::dispatchShell().
 *
 * @package       Cake.Test.TestApp.Plugin.TestDispatchPlugin.Console.Command
 */
class DispatchedShell extends Shell
{
    /**
     * Skips the welcome message.
     *
     * @return void
     */
    public function startup()
    {
    }

    /**
     * Returns the arguments.
     *
     * @return string
     */
    public function main()
    {
        return 'main: ' . implode(' ', $this->args);
    }

    /**
     * Returns the arguments.
     *
     * @return string
     */
    public function hello()
    {
        return 'hello: ' . implode(' ', $this->args);
    }
}
