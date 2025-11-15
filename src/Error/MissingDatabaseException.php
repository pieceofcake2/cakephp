<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html
 * @package       Cake.Error
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Error;

/**
 * Runtime Exceptions for ConnectionManager
 *
 * @package       Cake.Error
 */
class MissingDatabaseException extends CakeException
{
    protected string $_messageTemplate = 'Database connection "%s" could not be found.';
}
