<?php
/**
 * CacheHelperTest file
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

use CacheTestController;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Network\CakeRequest;
use Cake\TestSuite\CakeTestCase;
use Cake\View\Helper\CacheHelper;
use Cake\View\View;

App::uses('CacheTestController', 'Controller');

/**
 * CacheHelperTest class
 *
 * @package       Cake.Test.Case.View.Helper
 */
class CacheHelperTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $_GET = [];
        App::build([
            'Controller' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Controller' . DS],
        ]);
        $request = new CakeRequest();
        $this->Controller = new CacheTestController($request);
        $View = new View($this->Controller);
        $this->Cache = new CacheHelper($View);
        Configure::write('Cache.check', true);
        Configure::write('Cache.disable', false);
        App::build([
            'View' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'View' . DS],
        ], App::RESET);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        clearCache();
        unset($this->Cache);

        parent::tearDown();
    }

    /**
     * test cache parsing with no cake:nocache tags in view file.
     *
     * @return void
     */
    public function testLayoutCacheParsingNoTagsInView(): void
    {
        $this->Controller->cache_parsing();
        $this->Controller->request->addParams([
            'controller' => 'cache_test',
            'action' => 'cache_parsing',
            'pass' => [],
            'named' => [],
        ]);
        $this->Controller->cacheAction = 21600;
        $this->Controller->request->here = '/cacheTest/cache_parsing';

        $View = new View($this->Controller);
        $result = $View->render('index');
        $this->assertDoesNotMatchRegularExpression('/cake:nocache/', $result);
        $this->assertDoesNotMatchRegularExpression('/php echo/', $result);

        $filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';
        $this->assertTrue(file_exists($filename));

        $contents = file_get_contents($filename);
        $this->assertMatchesRegularExpression('/php echo \$variable/', $contents);
        $this->assertMatchesRegularExpression('/php echo microtime()/', $contents);
        $this->assertMatchesRegularExpression('/clark kent/', $result);

        unlink($filename);
    }

    /**
     * Test that the cached view creates a global controller by its class name.
     *
     * @return void
     */
    public function testCacheGlobalController(): void
    {
        $this->Controller->cache_parsing();
        $this->Controller->request->addParams([
            'controller' => 'cache_test',
            'action' => 'cache_parsing',
            'pass' => [],
            'named' => [],
        ]);
        $this->Controller->cacheAction = 21600;
        $this->Controller->request->here = '/cacheTest/cache_parsing';
        $filename = CACHE . 'views' . DS . 'cachetest_cache_parsing.php';

        $View = new View($this->Controller);
        $View->render('index');
        $contents = file_get_contents($filename);
        unlink($filename);
        $this->assertStringContainsString("App::className('CacheTest', 'Controller', 'Controller');", $contents);
        $this->assertStringContainsString('$controller = new CacheTestController($request, $response);', $contents);
    }
}
