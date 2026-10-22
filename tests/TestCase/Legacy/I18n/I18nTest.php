<?php
/**
 * I18nTest file
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
 * @package       Cake.Test.Case.I18n
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\I18n;

use Cake\Cache\Cache;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\I18n\I18n;
use Cake\Model\Datasource\CakeSession;
use Cake\TestSuite\CakeTestCase;

/**
 * I18nTest class
 *
 * @package       Cake.Test.Case.I18n
 */
class I18nTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        Cache::delete('object_map', '_cake_core_');
        App::build([
            'Locale' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Locale' . DS],
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ], App::RESET);
        CakePlugin::load(['TestLegacyPlugin']);

        // Clear I18n state for each test
        I18n::clear();
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        Cache::delete('object_map', '_cake_core_');
        CakeSession::destroy();
        App::build();
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * testPoRulesZero method
     *
     * @return void
     */
    public function testPoRulesZero(): void
    {
        Configure::write('Config.language', 'rule_0_po');
        $this->assertRulesZero();
    }

    /**
     * testMoRulesZero method
     *
     * @return void
     */
    public function testMoRulesZero(): void
    {
        Configure::write('Config.language', 'rule_0_mo');
        $this->assertRulesZero();
    }

    /**
     * Assertions for rules zero.
     *
     * @return void
     */
    public function assertRulesZero(): void
    {
        $singular = $this->_singular();
        $this->assertEquals('Plural Rule 0 (translated)', $singular);

        $plurals = $this->_plural();
        $this->assertTrue(in_array('0 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('1 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('2 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('3 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('4 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('5 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('6 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('7 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('8 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('9 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('10 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('11 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('12 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('13 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('14 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('15 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('16 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('17 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('18 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('19 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('20 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('21 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('22 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('23 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('24 ends with any # (translated)', $plurals));
        $this->assertTrue(in_array('25 ends with any # (translated)', $plurals));

        $coreSingular = $this->_singularFromCore();
        $this->assertEquals('Plural Rule 0 (from core translated)', $coreSingular);

        $corePlurals = $this->_pluralFromCore();
        $this->assertTrue(in_array('0 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('1 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('2 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('3 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('4 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('5 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('6 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('7 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('8 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('9 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('10 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('11 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('12 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('13 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('14 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('15 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('16 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('17 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('18 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('19 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('20 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('21 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('22 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('23 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('24 ends with any # (from core translated)', $corePlurals));
        $this->assertTrue(in_array('25 ends with any # (from core translated)', $corePlurals));
    }

    /**
     * testPluginTranslation method
     *
     * @return void
     */
    public function testPluginTranslation(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);

        Configure::write('Config.language', 'po');
        $singular = $this->_domainSingular();
        $this->assertEquals('Plural Rule 1 (from plugin)', $singular);

        $plurals = $this->_domainPlural();
        $this->assertTrue(in_array('0 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('1 = 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('2 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('3 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('4 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('5 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('6 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('7 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('8 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('9 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('10 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('11 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('12 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('13 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('14 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('15 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('16 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('17 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('18 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('19 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('20 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('21 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('22 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('23 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('24 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('25 = 0 or > 1 (from plugin)', $plurals));
    }

    /**
     * Test that Configure::read('I18n.preferApp') will prefer app.
     *
     * @return void
     */
    public function testPluginTranslationPreferApp(): void
    {
        // Reset internally stored entries
        I18n::clear();
        Cache::clear(false, '_cake_core_');

        Configure::write('I18n.preferApp', true);

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);

        Configure::write('Config.language', 'po');
        try {
            $singular = $this->_domainSingular();
            $this->assertEquals('Plural Rule 1', $singular);

            $plurals = $this->_domainPlural();
            $this->assertTrue(in_array('0 = 0 or > 1', $plurals));
        } finally {
            // The translations of the application are cached for the domain of the plugin
            I18n::clear();
            Cache::clear(false, '_cake_core_');
        }
    }

    /**
     * Test that translations are read from the resources/locales directory of a plugin.
     *
     * @return void
     */
    public function testPluginTranslationInResourcesLocales(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'plugins' . DS],
        ]);
        CakePlugin::load('TestNamespacedPlugin');

        Configure::write('Config.language', 'po');
        $result = __d('test_namespaced_plugin', 'Plural Rule 1');
        $this->assertSame('Plural Rule 1 (from resources/locales)', $result);
    }

    /**
     * testCategory method
     *
     * @return void
     */
    public function testCategory(): void
    {
        Configure::write('Config.language', 'po');
        // Test with default (I18n constant) category.
        $category = $this->_category();
        $this->assertEquals('Monetary Po (translated)', $category);
        // Test with category number represenation.
        $category = $this->_category(3);
        $this->assertEquals('Monetary Po (translated)', $category);
    }

    /**
     * testPluginCategory method
     *
     * @return void
     */
    public function testPluginCategory(): void
    {
        Configure::write('Config.language', 'po');

        $singular = $this->_domainCategorySingular();
        $this->assertEquals('Monetary Plural Rule 1 (from plugin)', $singular);

        $plurals = $this->_domainCategoryPlural();
        $this->assertTrue(in_array('Monetary 0 = 0 or > 1 (from plugin)', $plurals));
        $this->assertTrue(in_array('Monetary 1 = 1 (from plugin)', $plurals));
    }

    /**
     * testTimeDefinition method
     *
     * @return void
     */
    public function testTimeDefinition(): void
    {
        Configure::write('Config.language', 'po');
        $result = __c('d_fmt', 5);
        $expected = '%m/%d/%Y';
        $this->assertEquals($expected, $result);

        $result = __c('am_pm', 5);
        $expected = ['AM', 'PM'];
        $this->assertEquals($expected, $result);

        $result = __c('abmon', 5);
        $expected = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        $this->assertEquals($expected, $result);
    }

    /**
     * testLoadLocaleDefinition method
     *
     * @return void
     */
    public function testLoadLocaleDefinition(): void
    {
        $path = current(App::path('locales'));
        $result = I18n::loadLocaleDefinition($path . 'nld' . DS . 'LC_TIME');
        $expected = ['zondag', 'maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag'];
        $this->assertSame($expected, $result['day']);
    }

    /**
     * Singular method
     *
     * @return void
     */
    protected function _domainCategorySingular($domain = 'test_legacy_plugin', $category = 3): mixed
    {
        $singular = __dc($domain, 'Plural Rule 1', $category);

        return $singular;
    }

    /**
     * Plural method
     *
     * @return void
     */
    protected function _domainCategoryPlural($domain = 'test_legacy_plugin', $category = 3): mixed
    {
        $plurals = [];
        for ($number = 0; $number <= 25; $number++) {
            $plurals[] = sprintf(__dcn($domain, '%d = 1', '%d = 0 or > 1', (float)$number, $category), (float)$number);
        }

        return $plurals;
    }

    /**
     * Singular method
     *
     * @return void
     */
    protected function _domainSingular($domain = 'test_legacy_plugin'): mixed
    {
        $singular = __d($domain, 'Plural Rule 1');

        return $singular;
    }

    /**
     * Plural method
     *
     * @return void
     */
    protected function _domainPlural($domain = 'test_legacy_plugin'): mixed
    {
        $plurals = [];
        for ($number = 0; $number <= 25; $number++) {
            $plurals[] = sprintf(__dn($domain, '%d = 1', '%d = 0 or > 1', (float)$number), (float)$number);
        }

        return $plurals;
    }

    /**
     * category method
     *
     * @return void
     */
    protected function _category($category = I18n::LC_MONETARY): mixed
    {
        $singular = __c('Plural Rule 1', $category);

        return $singular;
    }

    /**
     * Singular method
     *
     * @return void
     */
    protected function _singular(): mixed
    {
        $singular = __('Plural Rule 1');

        return $singular;
    }

    /**
     * Plural method
     *
     * @param int $upTo For numbers upto (default to 25)
     * @return void
     */
    protected function _plural($upTo = 25): mixed
    {
        $plurals = [];
        for ($number = 0; $number <= $upTo; $number++) {
            $plurals[] = sprintf(__n('%d = 1', '%d = 0 or > 1', (float)$number), (float)$number);
        }

        return $plurals;
    }

    /**
     * singularFromCore method
     *
     * @return void
     */
    protected function _singularFromCore(): mixed
    {
        $singular = __('Plural Rule 1 (from core)');

        return $singular;
    }

    /**
     * pluralFromCore method
     *
     * @param int $upTo For numbers upto (default to 25)
     * @return void
     */
    protected function _pluralFromCore($upTo = 25): mixed
    {
        $plurals = [];
        for ($number = 0; $number <= $upTo; $number++) {
            $plurals[] = sprintf(__n('%d = 1 (from core)', '%d = 0 or > 1 (from core)', (float)$number), (float)$number);
        }

        return $plurals;
    }
}
