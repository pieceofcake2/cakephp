<?php
/**
 * Test App ControllerPost Model
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
 * @since         CakePHP(tm) v 1.2.0.5436
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * ControllerPost class
 *
 * @package       Cake.Test.TestApp.Model
 */
class ControllerPost extends CakeTestModel
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
     * lastQuery property
     *
     * @var mixed
     */
    public $lastQuery = null;

    /**
     * beforeFind method
     *
     * @param mixed $query
     * @return void
     */
    public function beforeFind($query): void
    {
        $this->lastQuery = $query;
    }

    /**
     * find method
     *
     * @param string $type
     * @param array $options
     * @return void
     */
    public function find($type = 'first', $options = []): mixed
    {
        if ($type === 'popular') {
            $conditions = [$this->name . '.' . $this->primaryKey . ' > ' => '1'];
            $options = Hash::merge($options, compact('conditions'));

            return parent::find('all', $options);
        }

        return parent::find($type, $options);
    }
}
