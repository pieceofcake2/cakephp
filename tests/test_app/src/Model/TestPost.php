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

use Cake\Model\Datasource\DataSource;

/**
 * Helper class for testing with mocked datasources
 *
 * @property Author $Author
 */
class TestPost extends Post
{
    public $belongsTo = [
        'Author' => [
            'className' => 'TestAuthor',
        ],
    ];

    protected $_dataSourceObject = null;

    public $dataForAfterSave = [];

    /**
     * Helper method to set a datasource object
     *
     * @param DataSource $object The datasource object
     * @return void
     */
    public function setDataSourceObject($object): void
    {
        $this->_dataSourceObject = $object;
    }

    /**
     * Overwritten in order to return the directly set datasource object if
     * available
     *
     * @return DataSource
     */
    public function getDataSource(): mixed
    {
        if ($this->_dataSourceObject !== null) {
            return $this->_dataSourceObject;
        }

        return parent::getDataSource();
    }
}
