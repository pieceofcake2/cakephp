<?php
/**
 * CakeFixtureManager file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite.Fixture
 * @since         CakePHP v 2.5
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\TestSuite\Fixture;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use ReflectionProperty;

/**
 * Test Case for CakeFixtureManager class
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeFixtureManagerTest extends CakeTestCase
{
    /**
     * fixtures property
     *
     * @var array
     */
    public $fixtures = ['plugin.test_legacy_plugin.legacy_comment'];

    /**
     * The fixtures are loaded before setUp(), so the plugin is loaded here.
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]], App::RESET);
        CakePlugin::load('TestLegacyPlugin');
    }

    /**
     * tearDownAfterClass
     *
     * @return void
     */
    public static function tearDownAfterClass(): void
    {
        CakePlugin::unload();
        App::build();
    }

    /**
     * Test that a global fixture class is found in the Test/Fixture directory of a legacy plugin.
     *
     * @return void
     */
    public function testLoadLegacyPluginFixture(): void
    {
        $loaded = new ReflectionProperty($this->fixtureManager, '_loaded');
        if (PHP_VERSION_ID < 80100) {
            $loaded->setAccessible(true);
        }
        $fixtures = $loaded->getValue($this->fixtureManager);

        $this->assertInstanceOf('LegacyCommentFixture', $fixtures['plugin.test_legacy_plugin.legacy_comment']);
        $this->assertSame('LegacyComment', $fixtures['plugin.test_legacy_plugin.legacy_comment']->name);
        $this->assertSame('legacy_comments', $fixtures['plugin.test_legacy_plugin.legacy_comment']->table);

        $LegacyComment = ClassRegistry::init(['class' => 'LegacyComment', 'table' => 'legacy_comments']);
        $this->assertSame(2, $LegacyComment->find('count'));
    }
}
