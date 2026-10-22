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

namespace TestApp\Test\Fixture;

use Cake\TestSuite\Fixture\CakeTestFixture;

/**
 * Fixture that uses a test connection that is not configured
 */
class MissingConnectionFixture extends CakeTestFixture
{
    /**
     * @var string
     */
    public $useDbConfig = 'test_missing_connection';

    /**
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
    ];
}
