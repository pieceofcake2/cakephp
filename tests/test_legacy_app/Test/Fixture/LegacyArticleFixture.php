<?php
/**
 * LegacyArticleFixture
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.TestApp.Test.Fixture
 * @since         CakePHP(tm) v 2.12
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * LegacyArticleFixture
 *
 * @package       Cake.Test.TestApp.Test.Fixture
 */
class LegacyArticleFixture extends CakeTestFixture
{
    /**
     * fields property
     *
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'title' => ['type' => 'string', 'null' => false],
    ];

    /**
     * records property
     *
     * @var array
     */
    public $records = [
        ['id' => 1, 'title' => 'First Legacy Article'],
        ['id' => 2, 'title' => 'Second Legacy Article'],
    ];
}
