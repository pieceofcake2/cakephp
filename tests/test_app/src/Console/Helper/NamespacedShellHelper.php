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

namespace TestApp\Console\Helper;

use Cake\Console\Helper\BaseShellHelper;

/**
 * Shell helper in a namespace
 */
class NamespacedShellHelper extends BaseShellHelper
{
    /**
     * @param array $args The arguments
     * @return void
     */
    public function output($args)
    {
        $this->_consoleOutput->write('namespaced');
    }
}
