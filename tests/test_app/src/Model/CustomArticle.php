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

/**
 * CustomArticle class
 *
 * @package       Cake.Test.Case.Model
 */
class CustomArticle extends AppModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public $useTable = 'articles';

    /**
     * findMethods property
     *
     * @var array
     */
    public $findMethods = ['unPublished' => true];

    /**
     * belongsTo property
     *
     * @var array
     */
    public $belongsTo = ['User'];

    /**
     * _findUnPublished custom find
     *
     * @return array
     */
    protected function _findUnPublished($state, $query, $results = []): mixed
    {
        if ($state === 'before') {
            $query['conditions']['published'] = 'N';

            return $query;
        }

        return $results;
    }

    /**
     * Alters title data
     *
     * @param array $options Options passed from Model::save().
     * @return bool True if validate operation should continue, false to abort
     * @see Model::save()
     */
    public function beforeValidate($options = []): void
    {
        $this->data[$this->alias]['title'] = 'foo';
        if ($this->findMethods['unPublished'] === true) {
            $this->findMethods['unPublished'] = false;
        } else {
            $this->findMethods['unPublished'] = 'true again';
        }
    }
}
