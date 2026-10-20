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
 * PaginatorCustomPost class
 *
 * @package       Cake.Test.Case.Controller.Component
 */
class PaginatorCustomPost extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'posts';

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['Author'];

    /**
     * findMethods property
     *
     * @var array
     */
    public $findMethods = [
        'published' => true,
        'totals' => true,
        'totalsOperation' => true,
    ];

    /**
     * _findPublished custom find
     *
     * @param string $state
     * @param array $query
     * @param array $results
     * @return array
     */
    protected function _findPublished($state, $query, $results = []): mixed
    {
        if ($state === 'before') {
            $query['conditions']['published'] = 'Y';

            return $query;
        }

        return $results;
    }

    /**
     * _findTotals custom find
     *
     * @param string $state
     * @param array $query
     * @param array $results
     * @return array
     */
    protected function _findTotals($state, $query, $results = []): mixed
    {
        if ($state === 'before') {
            $query['fields'] = ['author_id'];
            $this->virtualFields['total_posts'] = "COUNT({$this->alias}.id)";
            $query['fields'][] = 'total_posts';
            $query['group'] = ['author_id'];
            $query['order'] = ['author_id' => 'ASC'];

            return $query;
        }
        $this->virtualFields = [];

        return $results;
    }

    /**
     * _findTotalsOperation custom find
     *
     * @param string $state
     * @param array $query
     * @param array $results
     * @return array
     */
    protected function _findTotalsOperation($state, $query, $results = []): mixed
    {
        if ($state === 'before') {
            if (!empty($query['operation']) && $query['operation'] === 'count') {
                unset($query['limit']);
                $query['recursive'] = -1;
                $query['fields'] = ['COUNT(DISTINCT author_id) AS count'];

                return $query;
            }

            $query['recursive'] = 0;
            $query['callbacks'] = 'before';
            $query['fields'] = ['author_id', 'Author.user'];
            $this->virtualFields['total_posts'] = "COUNT({$this->alias}.id)";
            $query['fields'][] = 'total_posts';
            $query['group'] = ['author_id', 'Author.user'];
            $query['order'] = ['author_id' => 'ASC'];

            return $query;
        }
        $this->virtualFields = [];

        return $results;
    }
}
