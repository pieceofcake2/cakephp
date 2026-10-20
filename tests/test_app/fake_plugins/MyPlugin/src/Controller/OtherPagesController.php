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

namespace MyPlugin\Controller;

/**
 * OtherPagesController class
 *
 * @package       Cake.Test.Case.Routing
 */
class OtherPagesController extends MyPluginAppController
{
    /**
     * uses property
     *
     * @var array|bool|null
     */
    public $uses = [];

    /**
     * display method
     *
     * @param string $page
     * @return string
     */
    public function display($page = null): mixed
    {
        return $page;
    }

    /**
     * index method
     *
     * @return bool
     */
    public function index(): mixed
    {
        return true;
    }
}
