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

namespace TestApp\Model;

use Cake\Model\Model;

/**
 * ContactTag class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class ContactTag extends Model
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * schema definition
     *
     * @var array
     */
    protected $_schema = [
        'id' => ['type' => 'integer', 'null' => false, 'default' => '', 'length' => '8'],
        'name' => ['type' => 'string', 'null' => false, 'default' => '', 'length' => '255'],
        'created' => ['type' => 'date', 'null' => true, 'default' => '', 'length' => ''],
        'modified' => ['type' => 'datetime', 'null' => true, 'default' => '', 'length' => null],
    ];
}
