<?php
/**
 * BasicsTest file
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
 * @package       Cake.Test.Case
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy;

use Cake\Core\App;
use Cake\Core\Configure;
use Cake\TestSuite\CakeTestCase;

require_once CAKE . 'basics.php';

/**
 * BasicsTest class
 *
 * @package       Cake.Test.Case
 */
class BasicsTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Locale' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Locale' . DS],
        ]);
    }

    /**
     * test __()
     *
     * @return void
     */
    public function testTranslate(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __('Plural Rule 1');
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __('Plural Rule 1 (from core)');
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s', 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s %s', 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s and a null argument', null);
        $expected = 'Some string with %s and a null argument';
        $this->assertEquals($expected, $result);

        $result = __('Some string with multiple %s%s, first being null', null, 'arguments');
        $expected = 'Some string with multiple arguments, first being null';
        $this->assertEquals($expected, $result);

        $result = __('Some string with %s %s', ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __('Testing %2$s %1$s', 'order', 'different');
        $expected = 'Testing different order';
        $this->assertEquals($expected, $result);

        $result = __('Testing %2$s %1$s', ['order', 'different']);
        $expected = 'Testing different order';
        $this->assertEquals($expected, $result);

        $result = __('Testing %.2f number', 1.2345);
        $expected = 'Testing 1.23 number';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __d()
     *
     * @return void
     */
    public function testTranslateDomain(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __d('default', 'Plural Rule 1');
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Plural Rule 1');
        $expected = 'Plural Rule 1';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Plural Rule 1 (from core)');
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Some string with %s', 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Some string with %s %s', 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __d('core', 'Some string with %s %s', ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);
    }

    /**
     * test __c()
     *
     * @return void
     */
    public function testTranslateCategory(): void
    {
        Configure::write('Config.language', 'rule_1_po');

        $result = __c('Plural Rule 1', 6);
        $expected = 'Plural Rule 1 (translated)';
        $this->assertEquals($expected, $result);

        $result = __c('Plural Rule 1 (from core)', 6);
        $expected = 'Plural Rule 1 (from core translated)';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %s', 6, 'arguments');
        $expected = 'Some string with arguments';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %s %s', 6, 'multiple', 'arguments');
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);

        $result = __c('Some string with %s %s', 6, ['multiple', 'arguments']);
        $expected = 'Some string with multiple arguments';
        $this->assertEquals($expected, $result);
    }
}
