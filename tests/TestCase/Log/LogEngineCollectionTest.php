<?php
/**
 * LogEngineCollectionTest file
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
 * @package       Cake.Test.Case.Log
 * @since         CakePHP(tm) v 2.4
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Log;

use Cake\Error\CakeLogException;
use Cake\Log\CakeLogInterface;
use Cake\Log\Engine\FileLog;
use Cake\Log\LogEngineCollection;
use Cake\TestSuite\CakeTestCase;

/**
 * LoggerEngineLog class
 */
class LoggerEngineLog extends FileLog
{
}
class_alias(LoggerEngineLog::class, 'App\\Log\\Engine\\LoggerEngineLog');

/**
 * LogEngineCollectionTest class
 *
 * @package       Cake.Test.Case.Log
 */
class LogEngineCollectionTest extends CakeTestCase
{
    public $Collection;

    /**
     * Start test callback
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->Collection = new LogEngineCollection();
    }

    /**
     * test load
     *
     * @return void
     */
    public function testLoad(): void
    {
        $result = $this->Collection->load('key', ['engine' => 'File']);
        $this->assertInstanceOf(CakeLogInterface::class, $result);
    }

    /**
     * test load with deprecated Log suffix
     *
     * @return void
     */
    public function testLoadWithSuffix(): void
    {
        $result = $this->Collection->load('key', ['engine' => 'FileLog']);
        $this->assertInstanceOf(CakeLogInterface::class, $result);
    }

    /**
     * test that engines starting with Log also work properly
     *
     * @return void
     */
    public function testLoadWithSuffixAtBeginning(): void
    {
        $result = $this->Collection->load('key', ['engine' => 'LoggerEngine']);
        $this->assertInstanceOf(CakeLogInterface::class, $result);
    }

    /**
     * test load with invalid Log
     *
     * @return void
     */
    public function testLoadInvalid(): void
    {
        $this->expectException(CakeLogException::class);
        $result = $this->Collection->load('key', ['engine' => 'ImaginaryFile']);
        $this->assertInstanceOf(CakeLogInterface::class, $result);
    }
}
