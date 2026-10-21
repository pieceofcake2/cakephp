<?php
/**
 * Test App SomethingElse Model
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
 * SomethingElse class
 *
 * @package       Cake.Test.TestApp.Model
 */
class SomethingElse extends CakeTestModel
{
    /**
     * name property
     *
     * @var string
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
     * @return array
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
