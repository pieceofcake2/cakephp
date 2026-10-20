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

/**
 * ContactNonStandardPk class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class ContactNonStandardPk extends Contact
{
    /**
     * primaryKey property
     *
     * @var string|null
     */
    public $primaryKey = 'pk';

    /**
     * schema method
     *
     * @return array|null
     */
    public function schema($field = false): mixed
    {
        $this->_schema = parent::schema();
        $this->_schema['pk'] = $this->_schema['id'];
        unset($this->_schema['id']);

        return $this->_schema;
    }
}
