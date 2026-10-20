<?php
/**
 * Test App OtherPagesController Controller
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
 * @package       Cake.Test.TestApp.Plugin.TestDispatchLegacyPlugin.Controller
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

App::uses('TestDispatchLegacyPluginAppController', 'TestDispatchLegacyPlugin.Controller');

/**
 * OtherPagesController class
 *
 * @package       Cake.Test.TestApp.Plugin.TestDispatchLegacyPlugin.Controller
 */
class OtherPagesController extends TestDispatchLegacyPluginAppController
{
    /**
     * uses property
     *
     * @var array
     */
    public $uses = [];

    /**
     * display method
     *
     * @param string $page
     * @return void
     */
    public function display($page = null): mixed
    {
        return $page;
    }

    /**
     * index method
     *
     * @return void
     */
    public function index(): mixed
    {
        return true;
    }
}
