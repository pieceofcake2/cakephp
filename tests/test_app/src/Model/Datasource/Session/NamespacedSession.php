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

namespace TestApp\Model\Datasource\Session;

use Cake\Model\Datasource\Session\CakeSessionHandlerInterface;

/**
 * Session handler in a namespace
 */
class NamespacedSession implements CakeSessionHandlerInterface
{
    /**
     * @return bool
     */
    public function open()
    {
        return true;
    }

    /**
     * @return bool
     */
    public function close()
    {
        return true;
    }

    /**
     * @param mixed $id Argument
     * @return string
     */
    public function read($id)
    {
        return '';
    }

    /**
     * @param mixed $id Argument
     * @param mixed $data Argument
     * @return bool
     */
    public function write($id, $data)
    {
        return true;
    }

    /**
     * @param mixed $id Argument
     * @return bool
     */
    public function destroy($id)
    {
        return true;
    }

    /**
     * @param mixed $expires Argument
     * @return bool
     */
    public function gc($expires = null)
    {
        return true;
    }
}
