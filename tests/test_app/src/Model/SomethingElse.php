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
 * SomethingElse class
 *
 * @package       Cake.Test.Case.Model
 */
class SomethingElse extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'SomethingElse';

    /**
     * hasAndBelongsToMany property
     *
     * @var array
     */
    public $hasAndBelongsToMany = ['Something' => ['with' => 'JoinThing']];

    /**
     * afterFind callBack
     *
     * @param array $results
     * @param bool $primary
     * @return mixed
     */
    public function afterFind($results, $primary = false): mixed
    {
        foreach ($results as $key => $result) {
            if (!empty($result[$this->alias]) && is_array($result[$this->alias])) {
                $results[$key][$this->alias]['afterFind'] = 'Successfully added by AfterFind';
            }
        }

        return $results;
    }
}
