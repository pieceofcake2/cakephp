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
 * Author class
 *
 * @package       Cake.Test.Case.Model
 */
class Author extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Author';

    /**
     * hasMany property
     *
     * @var array
     */
    public $hasMany = ['Post'];

    /**
     * afterFind method
     *
     * @param array $results
     * @return mixed
     */
    public function afterFind($results, $primary = false): mixed
    {
        $results[0]['Author']['test'] = 'working';

        return $results;
    }
}
