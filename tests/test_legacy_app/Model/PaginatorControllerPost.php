<?php
/**
 * Test App PaginatorControllerPost Model
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * PaginatorControllerPost class
 *
 * @package       Cake.Test.TestApp.Model
 */
class PaginatorControllerPost extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'posts';

    /**
     * invalidFields property
     *
     * @var array
     */
    public $invalidFields = ['name' => 'error_msg'];

    /**
     * lastQueries property
     *
     * @var array
     */
    public $lastQueries = [];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['PaginatorAuthor' => ['foreignKey' => 'author_id']];

    /**
     * beforeFind method
     *
     * @param mixed $query
     * @return void
     */
    public function beforeFind($query): void
    {
        array_unshift($this->lastQueries, $query);
    }

    /**
     * find method
     *
     * @param mixed $type
     * @param array $options
     * @return void
     */
    public function find($conditions = null, $fields = [], $order = null, $recursive = null): mixed
    {
        if ($conditions === 'popular') {
            $conditions = [$this->name . '.' . $this->primaryKey . ' > ' => '1'];
            $options = Hash::merge($fields, compact('conditions'));

            return parent::find('all', $options);
        }

        return parent::find($conditions, $fields);
    }
}
