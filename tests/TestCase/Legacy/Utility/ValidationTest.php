<?php
/**
 * ValidationTest file
 *
 * CakePHP(tm) Tests <https://book.cakephp.org/2.0/en/development/testing.html>
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Utility;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\Validation;

/**
 * Test Case for Validation Class
 *
 * @package       Cake.Test.Case.Utility
 */
class ValidationTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->_appEncoding = Configure::read('App.encoding');
        $this->_appLocale = [];
        foreach ([LC_MONETARY, LC_NUMERIC, LC_TIME] as $category) {
            $this->_appLocale[$category] = setlocale($category, 0);
            setlocale($category, 'en_US');
        }
        App::build([
            'Lib' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Lib' . DS],
        ]);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Configure::write('App.encoding', $this->_appEncoding);
        foreach ($this->_appLocale as $category => $locale) {
            setlocale($category, $locale);
        }

        parent::tearDown();
    }

    /**
     * test that phone and postal pass to other classes.
     *
     * @return void
     */
    public function testPhonePostalSsnPass(): void
    {
        $this->assertTrue(Validation::postal('text', null, 'testNl'));
        $this->assertTrue(Validation::phone('text', null, 'testDe'));
        $this->assertTrue(Validation::ssn('text', null, 'testNl'));
    }

    /**
     * test pass through failure on postal
     *
     * @return void
     */
    public function testPassThroughMethodFailure(): void
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            Validation::phone('text', null, 'testNl');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('Method phone does not exist on TestNlValidation unable to complete validation.', $warningMessage);
    }

    /**
     * test the pass through calling of an alternate locale with postal()
     *
     * @return void
     */
    public function testPassThroughClassFailure(): void
    {
        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            if ($errno === E_WARNING || $errno === E_USER_WARNING) {
                $warningTriggered = true;
                $warningMessage = $errstr;

                return true;
            }

            return false;
        }, E_WARNING | E_USER_WARNING);

        try {
            Validation::postal('text', null, 'AUTOFAIL');
        } finally {
            restore_error_handler();
        }

        $this->assertTrue($warningTriggered, 'Expected warning was not triggered');
        $this->assertSame('Could not find AUTOFAILValidation class, unable to complete validation.', $warningMessage);
    }

    /**
     * test pass through method
     *
     * @return void
     */
    public function testPassThroughMethod(): void
    {
        $this->assertTrue(Validation::postal('text', null, 'testNl'));
    }
}
