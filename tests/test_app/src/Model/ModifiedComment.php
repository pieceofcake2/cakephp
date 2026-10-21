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
 * Modified Comment Class has afterFind Callback
 *
 * @package       Cake.Test.Case.Model
 */
class ModifiedComment extends CakeTestModel
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'Comment';

    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'comments';

    /**
     * Property used to toggle filtering of results
     *
     * @var bool
     */
    public $remove = false;

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['Article'];

    /**
     * afterFind callback
     *
     * @return mixed
     */
    public function afterFind($results, $primary = false): mixed
    {
        if (isset($results[0])) {
            $results[0]['Comment']['callback'] = 'Fire';
        }
        if ($this->remove) {
            return [];
        }

        return $results;
    }
}
