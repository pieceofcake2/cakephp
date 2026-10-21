<?php
/**
 * Test App ContactTagsContact Model
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
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ContactTagsContact class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ContactTagsContact extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var bool
     */
    public $useTable = false;

    /**
     * Default schema
     *
     * @var array
     */
    protected $_schema = [
        'contact_id' => ['type' => 'integer', 'null' => '', 'default' => '', 'length' => '8'],
        'contact_tag_id' => [
            'type' => 'integer', 'null' => '', 'default' => '', 'length' => '8',
        ],
    ];

    /**
     * schema method
     *
     * @return void
     */
    public function setSchema($schema): void
    {
        $this->_schema = $schema;
    }
}
