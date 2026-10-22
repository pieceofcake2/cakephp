<?php
/**
 * BootstrapTest file
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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Core;

use Cake\TestSuite\CakeTestCase;

/**
 * Tests the bootstrap of the framework.
 */
class BootstrapTest extends CakeTestCase
{
    /**
     * Test that the former path of the bootstrap in src/Cake/, which webroot/index.php of an
     * application includes, loads src/bootstrap.php, and only once.
     *
     * @return void
     */
    public function testFormerPath(): void
    {
        $this->assertContains(realpath(CAKE . 'bootstrap.php'), get_included_files());

        // Running the bootstrap again would fail, e.g. on define('TIME_START')
        $former = CAKE . 'Cake' . DS . 'bootstrap.php';
        $this->assertNotFalse(require_once $former);
        $this->assertContains(realpath($former), get_included_files());
    }
}
