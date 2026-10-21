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
 * ValidationTest1 class
 *
 * @package       Cake.Test.Case.Model
 */
class ValidationTest1 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'ValidationTest1';

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
    protected $_schema = [];

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
     * @return void
     */
    public function customValidationMethod($data): mixed
    {
        return $data === 1;
    }

    /**
     * Custom validator with parameters + default values
     *
     * @return array
     */
    public function customValidatorWithParams($data, $validator, $or = true, $ignoreOnSame = 'id'): mixed
    {
        $this->validatorParams = get_defined_vars();
        unset($this->validatorParams['this']);

        return true;
    }

    /**
     * Custom validator with message
     *
     * @return string
     */
    public function customValidatorWithMessage($data): mixed
    {
        return 'This field will *never* validate! Muhahaha!';
    }

    /**
     * Test validation with many parameters
     *
     * @return bool
     */
    public function customValidatorWithSixParams($data, $one = 1, $two = 2, $three = 3, $four = 4, $five = 5, $six = 6): mixed
    {
        $this->validatorParams = get_defined_vars();
        unset($this->validatorParams['this']);

        return true;
    }
}
