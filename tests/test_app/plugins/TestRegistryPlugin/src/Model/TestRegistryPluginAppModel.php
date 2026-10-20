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

namespace TestRegistryPlugin\Model;

use TestApp\Model\ClassRegisterModel;

/**
 * TestRegistryPluginAppModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class TestRegistryPluginAppModel extends ClassRegisterModel
{
    /**
     * tablePrefix property
     *
     * @var string|null
     */
    public $tablePrefix = 'something_';
}
