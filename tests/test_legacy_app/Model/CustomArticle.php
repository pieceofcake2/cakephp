<?php
/**
 * Test App CustomArticle Model
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

App::uses('AppModel', 'Model');

/**
 * CustomArticle class
 *
 * @package       Cake.Test.TestApp.Model
 */
class CustomArticle extends AppModel
{
    /**
     * useTable property
     *
     * @var string
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
