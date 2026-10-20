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
 * Model in a namespace with a behavior named by its class name
 */
class NamespacedArticle extends AppModel
{
    /**
     * @var string|false
     */
    public $useTable = false;

    /**
     * @var array
     */
    public $actsAs = ['TestApp\Model\Behavior\Namespaced' => ['priority' => 3]];
}
