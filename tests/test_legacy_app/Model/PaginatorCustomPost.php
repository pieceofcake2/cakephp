<?php
/**
 * Test App PaginatorCustomPost Model
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
 * PaginatorCustomPost class
 *
 * @package       Cake.Test.TestApp.Model
 */
class PaginatorCustomPost extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string
     */
    public $useTable = 'posts';

    /**
     * belongsTo property
     *
     * @var string
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
