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

/**
 * Example class
 *
 * @package       Cake.Test.Case.Model
 */
class Example extends AppModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * schema property
     *
     * @var array
     */
    protected $_schema = [
        'filefield' => [
            'type' => 'string',
            'length' => 254,
            'default' => null,
            'null' => true,
            'comment' => null,
        ],
    ];
}
