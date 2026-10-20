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

use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * ValidationTest2 class
 *
 * @package       Cake.Test.Case.Model
 */
class ValidationTest2 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'ValidationTest2';

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = false;

    /**
     * validate property
     *
     * @var array
     */
    public $validate = [
        'title' => 'notBlank',
        'published' => 'customValidationMethod',
        'body' => [
            'notBlank',
            '/^.{5,}$/s' => 'no matchy',
            '/^[0-9A-Za-z \\.]{1,}$/s',
        ],
    ];

    /**
     * customValidationMethod method
     *
     * @param mixed $data
     * @return bool
     */
    public function customValidationMethod($data): mixed
    {
        return $data === 1;
    }

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        return [];
    }
}
