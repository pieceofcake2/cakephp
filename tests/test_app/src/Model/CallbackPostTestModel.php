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
use Cake\TestSuite\Fixture\CakeTestModel;

/**
 * CallbackPostTestModel class
 *
 * @package       Cake.Test.Case.Model
 */
class CallbackPostTestModel extends CakeTestModel
{
    public $useTable = 'posts';

    /**
     * variable to control return of beforeValidate
     *
     * @var bool
     */
    public $beforeValidateReturn = true;

    /**
     * variable to control return of beforeSave
     *
     * @var bool
     */
    public $beforeSaveReturn = true;

    /**
     * variable to control return of beforeDelete
     *
     * @var bool
     */
    public $beforeDeleteReturn = true;

    /**
     * beforeSave callback
     *
     * @return bool|null
     */
    public function beforeSave($options = []): mixed
    {
        return $this->beforeSaveReturn;
    }

    /**
     * beforeValidate callback
     *
     * @param array $options Options passed from Model::save().
     * @return bool True if validate operation should continue, false to abort
     * @see Model::save()
     */
    public function beforeValidate($options = []): mixed
    {
        return $this->beforeValidateReturn;
    }

    /**
     * beforeDelete callback
     *
     * @return bool|null
     */
    public function beforeDelete($cascade = true): mixed
    {
        return $this->beforeDeleteReturn;
    }
}
