<?php
/**
 * ComponentCollectionTest file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://book.cakephp.org/2.0/en/development/testing.html CakePHP(tm) Tests
 * @package       Cake.Test.Case.Controller
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Controller;

use Cake\Controller\ComponentCollection;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingComponentException;
use Cake\TestSuite\CakeTestCase;

class ComponentCollectionTest extends CakeTestCase
{
    /**
     * setUp
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        App::build([
            'Controller/Component' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS . 'Component' . DS],
        ]);
        $this->Components = new ComponentCollection();
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Components);

        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoad(): void
    {
        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf('CookieComponent', $result);
        $this->assertInstanceOf('CookieComponent', $this->Components->Cookie);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Components->enabled('Cookie'));

        $result = $this->Components->load('Cookie');
        $this->assertSame($result, $this->Components->Cookie);
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Components->load('Cookie', ['className' => 'CookieAlias', 'somesetting' => true]);
        $this->assertInstanceOf('CookieAliasComponent', $result);
        $this->assertInstanceOf('CookieAliasComponent', $this->Components->Cookie);
        $this->assertTrue($this->Components->Cookie->settings['somesetting']);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Components->enabled('Cookie'));

        $result = $this->Components->load('Cookie');
        $this->assertInstanceOf('CookieAliasComponent', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load('TestLegacyPlugin');
        $result = $this->Components->load('SomeOther', ['className' => 'TestLegacyPlugin.Other']);
        $this->assertInstanceOf('OtherComponent', $result);
        $this->assertInstanceOf('OtherComponent', $this->Components->SomeOther);

        $result = $this->Components->loaded();
        $this->assertEquals(['Cookie', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
        CakePlugin::unload();
    }

    /**
     * test missingcomponent exception
     *
     * @return void
     */
    public function testLoadMissingComponent(): void
    {
        $this->expectException(MissingComponentException::class);
        $this->Components->load('ThisComponentShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin component.
     *
     * @return void
     */
    public function testLoadPluginComponent(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $result = $this->Components->load('TestLegacyPlugin.Other');
        $this->assertInstanceOf('OtherComponent', $result, 'Component class is wrong.');
        $this->assertInstanceOf('OtherComponent', $this->Components->Other, 'Class is wrong');
        App::build();
        CakePlugin::unload();
    }
}
