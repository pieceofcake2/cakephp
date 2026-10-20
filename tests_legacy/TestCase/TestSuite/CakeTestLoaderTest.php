<?php
/**
 * CakePHP : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * TestCakeTestLoader
 *
 * @package       Cake.Test.Case.TestSuite
 */
class TestCakeTestLoader extends CakeTestLoader
{
    public static function basePath(?array $params): ?string
    {
        return static::_basePath($params);
    }
}

/**
 * CakeTestLoaderTest
 *
 * @package       Cake.Test.Case.TestSuite
 */
class CakeTestLoaderTest extends CakeTestCase
{
    /**
     * Temporary plugins directory
     *
     * @var string
     */
    protected string $pluginsPath;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $this->pluginsPath = TMP . 'tests' . DS . 'cake_test_loader_plugins' . DS;

        $files = [
            'ModernLayoutPlugin' . DS . 'tests' . DS . 'TestCase' . DS . 'Model' . DS . 'ModernTest.php',
            'LegacyLayoutPlugin' . DS . 'Test' . DS . 'Case' . DS . 'Model' . DS . 'LegacyTest.php',
        ];
        foreach ($files as $file) {
            $path = $this->pluginsPath . $file;
            if (!is_dir(dirname($path))) {
                mkdir(dirname($path), 0777, true);
            }
            file_put_contents($path, '<?php' . PHP_EOL);
        }

        App::build(['Plugin' => [$this->pluginsPath]], App::RESET);
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        CakePlugin::unload('ModernLayoutPlugin');
        CakePlugin::unload('LegacyLayoutPlugin');
        $Folder = new Folder($this->pluginsPath);
        $Folder->delete();

        parent::tearDown();
    }

    /**
     * Test that the base path of app tests prefers tests/TestCase over Test/Case
     *
     * @return void
     */
    public function testBasePathApp(): void
    {
        $this->skipIf(!is_dir(TESTS . 'TestCase'), 'The app does not use the tests/TestCase layout.');
        $this->assertSame(TESTS . 'TestCase', TestCakeTestLoader::basePath(['app' => true]));
    }

    /**
     * Test the base path of core tests
     *
     * @return void
     */
    public function testBasePathCore(): void
    {
        $this->assertSame(CORE_TESTS . DS . 'TestCase', TestCakeTestLoader::basePath(['core' => true]));
    }

    /**
     * Test that the base path of plugin tests supports both the modern and legacy layouts
     *
     * @return void
     */
    public function testBasePathPlugin(): void
    {
        $this->assertSame(
            $this->pluginsPath . 'ModernLayoutPlugin' . DS . 'tests' . DS . 'TestCase',
            TestCakeTestLoader::basePath(['plugin' => 'ModernLayoutPlugin']),
        );
        $this->assertSame(
            $this->pluginsPath . 'LegacyLayoutPlugin' . DS . 'Test' . DS . 'Case',
            TestCakeTestLoader::basePath(['plugin' => 'LegacyLayoutPlugin']),
        );

        // Already loaded plugins
        $this->assertSame(
            $this->pluginsPath . 'ModernLayoutPlugin' . DS . 'tests' . DS . 'TestCase',
            TestCakeTestLoader::basePath(['plugin' => 'ModernLayoutPlugin']),
        );
    }

    /**
     * Test generateTestList() with plugins in the modern and legacy layouts
     *
     * @return void
     */
    public function testGenerateTestListPlugin(): void
    {
        $result = CakeTestLoader::generateTestList(['plugin' => 'ModernLayoutPlugin']);
        $this->assertSame(['Model' . DS . 'Modern'], array_values($result));

        $result = CakeTestLoader::generateTestList(['plugin' => 'LegacyLayoutPlugin']);
        $this->assertSame(['Model' . DS . 'Legacy'], array_values($result));
    }

    /**
     * Test generateTestList() with an unknown plugin
     *
     * @return void
     */
    public function testGenerateTestListMissingPlugin(): void
    {
        $this->assertSame([], CakeTestLoader::generateTestList(['plugin' => 'NotExistingPlugin']));
    }
}
