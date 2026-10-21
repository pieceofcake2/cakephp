<?php
/**
 * Test App ValidationTest1 Model
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.TestApp.Model
 * @since         CakePHP(tm) v 1.2.0.6464
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ValidationTest1 class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ValidationTest1 extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
     */
    public $name = 'ValidationTest1';

    /**
     * useTable property
     *
     * @var bool
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
     * @return array
     */
    public function customValidatorWithMessage($data): mixed
    {
        return 'This field will *never* validate! Muhahaha!';
    }

    /**
     * Test validation with many parameters
     *
     * @return void
     */
    public function customValidatorWithSixParams($data, $one = 1, $two = 2, $three = 3, $four = 4, $five = 5, $six = 6): mixed
    {
        $this->validatorParams = get_defined_vars();
        unset($this->validatorParams['this']);

        return true;
    }
}
