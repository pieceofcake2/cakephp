<?php
/**
 * LegacyClassLoaderTest file
 */

namespace Cake\Test\TestCase;

use Cake\Controller\Controller;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\LegacyClassLoader;
use Cake\Model\Model;
use Cake\TestSuite\CakeTestCase;

/**
 * LegacyClassLoaderTest class
 *
 * @package       Cake.Test.Case
 */
class LegacyClassLoaderTest extends CakeTestCase
{
    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();

        // Ensure App.namespace is set
        if (!Configure::check('App.namespace')) {
            Configure::write('App.namespace', 'App');
        }
    }

    /**
     * Test that App* classes are resolved from application namespace
     *
     * @return void
     */
    public function testAppClassResolution()
    {
        // Skip if AppController alias already exists from another test
        if (class_exists('AppController', false)) {
            $this->markTestSkipped('AppController alias already exists');
        }

        // Create a temporary namespaced AppController class
        if (!class_exists('App\Controller\AppController', false)) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            eval('namespace App\Controller; class AppController extends \Cake\Controller\Controller {}');
        }

        // Test that 'AppController' gets aliased to App\Controller\AppController
        $this->assertTrue(
            LegacyClassLoader::autoload('AppController'),
            'AppController should be resolved from App namespace',
        );

        $this->assertTrue(
            class_exists('AppController', false),
            'AppController alias should exist after autoload',
        );
    }

    /**
     * Test that App* classes respect custom namespace configuration
     *
     * @return void
     */
    public function testAppClassResolutionWithCustomNamespace()
    {
        // Skip if AppModel alias already exists from another test
        if (class_exists('AppModel', false)) {
            $this->markTestSkipped('AppModel alias already exists');
        }

        // Set custom namespace
        $originalNamespace = Configure::read('App.namespace');
        Configure::write('App.namespace', 'MyApp');

        // Create a temporary namespaced AppModel class in custom namespace
        if (!class_exists('MyApp\Model\AppModel', false)) {
            // phpcs:ignore Squiz.PHP.Eval.Discouraged
            eval('namespace MyApp\Model; class AppModel extends \Cake\Model\Model {}');
        }

        // Test that 'AppModel' gets aliased to MyApp\Model\AppModel
        $this->assertTrue(
            LegacyClassLoader::autoload('AppModel'),
            'AppModel should be resolved from custom namespace',
        );

        $this->assertTrue(
            class_exists('AppModel', false),
            'AppModel alias should exist after autoload',
        );

        // Restore original namespace
        Configure::write('App.namespace', $originalNamespace);
    }

    /**
     * Test that framework classes are still mapped correctly
     *
     * @return void
     */
    public function testFrameworkClassMapping()
    {
        // Test that non-App* classes still work via LegacyClassLoader
        $classMap = LegacyClassLoader::getClassMap();

        $this->assertArrayHasKey('Controller', $classMap);
        $this->assertEquals(Controller::class, $classMap['Controller']);

        $this->assertArrayHasKey('Model', $classMap);
        $this->assertEquals(Model::class, $classMap['Model']);
    }

    /**
     * Test that AppShell is not in the class map (should be dynamically resolved)
     *
     * @return void
     */
    public function testAppShellNotInClassMap()
    {
        $classMap = LegacyClassLoader::getClassMap();

        $this->assertArrayNotHasKey(
            'AppShell',
            $classMap,
            'AppShell should not be in LegacyClassLoader map - it should be dynamically resolved',
        );
    }

    /**
     * Test that App* classes only match correct naming convention
     *
     * @return void
     */
    public function testAppClassNamingConvention()
    {
        // 'App' alone should not be handled by App* resolution (it's in the class map)
        $classMap = LegacyClassLoader::getClassMap();
        $this->assertArrayHasKey('App', $classMap);
        $this->assertEquals(App::class, $classMap['App']);

        // 'Application' should not match App* pattern (lowercase after 'App')
        LegacyClassLoader::autoload('Application');
        $this->assertFalse(
            class_exists('Application', false),
            'Application should not be treated as App* class',
        );
    }
}
