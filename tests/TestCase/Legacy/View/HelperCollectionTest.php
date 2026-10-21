<?php
/**
 * HelperCollectionTest file
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
 * @package       Cake.Test.Case.View
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\View;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Error\MissingHelperException;
use Cake\TestSuite\CakeTestCase;
use Cake\View\HelperCollection;

/**
 * HelperCollectionTest
 *
 * @package       Cake.Test.Case.View
 */
class HelperCollectionTest extends CakeTestCase
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
            'View/Helper' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS . 'Helper' . DS],
        ]);
        $this->View = $this->getMock('View', [], [null]);
        $this->Helpers = new HelperCollection($this->View);
    }

    /**
     * tearDown
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload();
        unset($this->Helpers, $this->View);

        parent::tearDown();
    }

    /**
     * test triggering callbacks on loaded helpers
     *
     * @return void
     */
    public function testLoad(): void
    {
        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('HtmlHelper', $result);
        $this->assertInstanceOf('HtmlHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));
    }

    /**
     * test lazy loading of helpers
     *
     * @return void
     */
    public function testLazyLoad(): void
    {
        $result = $this->Helpers->Html;
        $this->assertInstanceOf('HtmlHelper', $result);

        $result = $this->Helpers->Form;
        $this->assertInstanceOf('FormHelper', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]]);
        $this->View->plugin = 'TestLegacyPlugin';
        CakePlugin::load(['TestLegacyPlugin']);
        $result = $this->Helpers->OtherHelper;
        $this->assertInstanceOf('OtherHelperHelper', $result);
    }

    /**
     * Tests loading as an alias
     *
     * @return void
     */
    public function testLoadWithAlias(): void
    {
        $result = $this->Helpers->load('Html', ['className' => 'HtmlAlias']);
        $this->assertInstanceOf('HtmlAliasHelper', $result);
        $this->assertInstanceOf('HtmlAliasHelper', $this->Helpers->Html);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html'], $result, 'loaded() results are wrong.');

        $this->assertTrue($this->Helpers->enabled('Html'));

        $result = $this->Helpers->load('Html');
        $this->assertInstanceOf('HtmlAliasHelper', $result);

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS]]);
        CakePlugin::load(['TestLegacyPlugin']);
        $result = $this->Helpers->load('SomeOther', ['className' => 'TestLegacyPlugin.OtherHelper']);
        $this->assertInstanceOf('OtherHelperHelper', $result);
        $this->assertInstanceOf('OtherHelperHelper', $this->Helpers->SomeOther);

        $result = $this->Helpers->loaded();
        $this->assertEquals(['Html', 'SomeOther'], $result, 'loaded() results are wrong.');
        App::build();
    }

    /**
     * test missinghelper exception
     *
     * @return void
     */
    public function testLoadMissingHelper(): void
    {
        $this->expectException(MissingHelperException::class);
        $this->Helpers->load('ThisHelperShouldAlwaysBeMissing');
    }

    /**
     * test loading a plugin helper.
     *
     * @return void
     */
    public function testLoadPluginHelper(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load(['TestLegacyPlugin']);
        $result = $this->Helpers->load('TestLegacyPlugin.OtherHelper');
        $this->assertInstanceOf('OtherHelperHelper', $result, 'Helper class is wrong.');
        $this->assertInstanceOf('OtherHelperHelper', $this->Helpers->OtherHelper, 'Class is wrong');

        App::build();
    }
}
