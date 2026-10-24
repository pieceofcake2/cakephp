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
 * Fixture in the namespace of a plugin, in a directory that Composer does not load: the fixture manager reads the
 * file, like the core fixtures in a package that depends on CakePHP
 */
class FallbackPostFixture extends CakeTestFixture
{
    /**
     * @var array
     */
    public $fields = [
        'id' => ['type' => 'integer', 'key' => 'primary'],
        'title' => ['type' => 'string', 'null' => false],
    ];
}
