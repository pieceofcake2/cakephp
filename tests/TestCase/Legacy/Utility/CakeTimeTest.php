<?php
/**
 * CakeTimeTest file
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
 * @package       Cake.Test.Case.View.Helper
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Utility;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\CakeTime;
use Locale;
use function PHP81_BC\strftime;

/**
 * CakeTimeTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CakeTimeTest extends CakeTestCase
{
    /**
     * Default system timezone identifier
     *
     * @var string
     */
    protected $_systemTimezoneIdentifier = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->Time = new CakeTime();
        $this->_systemTimezoneIdentifier = date_default_timezone_get();
        Configure::write('Config.language', 'eng');
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Time);
        $this->_restoreSystemTimezone();

        parent::tearDown();
    }

    /**
     * Restored the original system timezone
     *
     * @param string $timezoneIdentifier Timezone string
     * @return void
     */
    protected function _restoreSystemTimezone(): void
    {
        date_default_timezone_set($this->_systemTimezoneIdentifier);
    }

    /**
     * test converting time specifiers using a time definition localfe file
     *
     * @return void
     */
    public function testConvertSpecifiers(): void
    {
        App::build([
            'Locale' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Locale' . DS],
        ], App::RESET);
        Configure::write('Config.language', 'time_test');
        $time = strtotime('Thu Jan 14 11:43:39 2010');

        $result = $this->Time->convertSpecifiers('%a', $time);
        $expected = 'jue';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%A', $time);
        $expected = 'jueves';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%c', $time);
        $expected = 'jue %d ene %Y %H:%M:%S %Z';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%C', $time);
        $expected = '20';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%D', $time);
        $expected = '%m/%d/%y';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%b', $time);
        $expected = 'ene';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%h', $time);
        $expected = 'ene';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%B', $time);
        $expected = 'enero';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%n', $time);
        $expected = "\n";
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%n', $time);
        $expected = "\n";
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%p', $time);
        $expected = 'AM';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%P', $time);
        $expected = 'am';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%r', $time);
        $expected = '%I:%M:%S AM';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%R', $time);
        $expected = '11:43';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%t', $time);
        $expected = "\t";
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%T', $time);
        $expected = '%H:%M:%S';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%u', $time);
        $expected = 4;
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%x', $time);
        $expected = '%d/%m/%y';
        $this->assertEquals($expected, $result);

        $result = $this->Time->convertSpecifiers('%X', $time);
        $expected = '%H:%M:%S';
        $this->assertEquals($expected, $result);
    }

    /**
     * test formatting dates taking in account preferred i18n locale file
     *
     * @return void
     */
    public function testI18nFormat(): void
    {
        $resetLocale = Locale::getDefault();
        App::build([
            'Locale' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Locale' . DS],
        ], App::RESET);
        Configure::write('Config.language', 'time_test');

        $time = strtotime('Thu Jan 14 13:59:28 2010');

        Locale::setDefault('en_US');
        $result = $this->Time->i18nFormat($time);
        $expected = '14/01/10';
        $this->assertEquals($expected, $result);

        $result = $this->Time->i18nFormat($time, '%c');
        // @codingStandardsIgnoreStart
        $expected = 'jue 14 ene 2010 13:59:28 ' . mb_convert_encoding(strftime('%Z', $time), 'UTF-8', 'ISO-8859-1');
        // @codingStandardsIgnoreEnd
        $this->assertEquals($expected, $result);

        $result = $this->Time->i18nFormat($time, 'Time is %r, and date is %x');
        $expected = 'Time is 01:59:28 PM, and date is 14/01/10';
        $this->assertEquals($expected, $result);

        $time = strtotime('Wed Jan 13 13:59:28 2010');

        $result = $this->Time->i18nFormat($time);
        $expected = '13/01/10';
        $this->assertEquals($expected, $result);

        $result = $this->Time->i18nFormat($time, '%c');
        $expected = 'mié 13 ene 2010 13:59:28 ' . mb_convert_encoding(strftime('%Z', $time), 'UTF-8', 'ISO-8859-1');
        $this->assertEquals($expected, $result);

        $result = $this->Time->i18nFormat($time, 'Time is %r, and date is %x');
        $expected = 'Time is 01:59:28 PM, and date is 13/01/10';
        $this->assertEquals($expected, $result);

        $result = $this->Time->i18nFormat('invalid date', '%x', 'Date invalid');
        $expected = 'Date invalid';
        $this->assertEquals($expected, $result);

        Locale::setDefault($resetLocale);
    }
}
