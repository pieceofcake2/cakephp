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

namespace TestTwoPlugin\Test\Fixture;

use Cake\TestSuite\Fixture\CakeTestFixture;

/**
 * Fixture of a plugin with the same name as TestApp\Test\Fixture\NamespacedArticleFixture
 */
class NamespacedArticleFixture extends CakeTestFixture
{
    /**
     * @var string
     */
    public $table = 'plugin_namespaced_articles';

    /**
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'title' => ['type' => 'string', 'null' => false],
    ];
}
