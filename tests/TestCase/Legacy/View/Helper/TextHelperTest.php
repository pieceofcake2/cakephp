<?php
/**
 * TextHelperTest file
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
use Cake\View\Helper\TextHelper;
use Cake\View\View;

/**
 * TextHelperTestObject
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TextHelperTestObject extends TextHelper
{
    public function attach(CakeTextMock $string): void
    {
        $this->_engine = $string;
    }

    public function engine(): mixed
    {
        return $this->_engine;
    }
}

/**
 * CakeTextMock class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CakeTextMock
{
}

/**
 * TextHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class TextHelperTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->View = new View(null);
        $this->Text = new TextHelper($this->View);
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
        $Text = new TextHelperTestObject($this->View, ['engine' => 'TestAppEngine']);
        $this->assertInstanceOf('TestAppEngine', $Text->engine());

        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $Text = new TextHelperTestObject($this->View, ['engine' => 'TestLegacyPlugin.TestPluginEngine']);
        $this->assertInstanceOf('TestPluginEngine', $Text->engine());
        CakePlugin::unload('TestLegacyPlugin');
    }
}
