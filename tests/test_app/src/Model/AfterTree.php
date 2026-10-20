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
 * AfterTree class
 *
 * @package       Cake.Test.Case.Model
 */
class AfterTree extends NumberTree
{
    /**
     * name property
     *
     * @var string|null
     */
    public $name = 'AfterTree';

    /**
     * actsAs property
     *
     * @var array
     */
    public $actsAs = ['Tree'];

    /**
     * @param bool $created
     * @param array $options
     * @return bool|null
     */
    public function afterSave($created, $options = []): void
    {
        if ($created && isset($this->data['AfterTree'])) {
            $this->data['AfterTree']['name'] = 'Six and One Half Changed in AfterTree::afterSave() but not in database';
        }
    }
}
