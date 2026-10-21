<?php
/**
 * TimeHelperTest file
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

namespace Cake\Test\TestCase\Legacy\View\Helper;

use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\TimeHelper;
use Cake\View\View;

/**
 * TimeHelperTestObject class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TimeHelperTestObject extends TimeHelper
{
    public function attach(CakeTimeMock $cakeTime): void
    {
        $this->_engine = $cakeTime;
    }

    public function engine(): mixed
    {
        return $this->_engine;
    }
}

/**
 * CakeTimeMock class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CakeTimeMock
{
}

/**
 * TimeHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TimeHelperTest extends CakeTestCase
{
    public $Time = null;

    public $CakeTime = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View(null);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->View);

        parent::tearDown();
    }

    /**
     * test engine override
     *
     * @return void
     */
    public function testEngineOverride(): void
    {
        App::build([
            'Utility' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Utility' . DS],
        ], App::REGISTER);
        $Time = new TimeHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf('TestAppEngine', $Time->engine());

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $Time = new TimeHelperTestObject($this->View, ['engine' => 'TestLegacyPlugin.TestPluginEngine']);
        $this->assertInstanceOf('TestPluginEngine', $Time->engine());
        CakePlugin::unload('TestLegacyPlugin');
    }
}
