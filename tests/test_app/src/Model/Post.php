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
 * Post class
 *
 * @package       Cake.Test.Case.Model
 */
class Post extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Post';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['Author'];

    /**
     * @param array $query
     * @return array|bool|null true
     */
    public function beforeFind($queryData): mixed
    {
        if (isset($queryData['connection'])) {
            $this->useDbConfig = $queryData['connection'];
        }

        return true;
    }

    /**
     * @param array $results
     * @param bool $primary
     * @return mixed
     */
    public function afterFind($results, $primary = false): mixed
    {
        $this->useDbConfig = 'test';

        return $results;
    }
}
