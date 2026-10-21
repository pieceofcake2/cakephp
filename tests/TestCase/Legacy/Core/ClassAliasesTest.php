<?php
/**
 * ClassAliasesTest file
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
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Core;

use Cake\Core\App;
use Cake\TestSuite\CakeTestCase;
use Composer\Autoload\ClassLoader;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Tests the global class names of CakePHP 2 for the classes in the Cake namespace.
 */
class ClassAliasesTest extends CakeTestCase
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
            'Model' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Model' . DS],
        ]);
    }

    /**
     * Test that each class of the framework can be used by its global name.
     *
     * @return void
     */
    public function testEveryClassHasItsGlobalName(): void
    {
        $classes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CAKE, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = substr($file->getPathname(), strlen(CAKE));
            // The class files, by PSR-4; not the functions, the templates or the former paths in Cake/
            if ($file->getExtension() !== 'php' || !ctype_upper($file->getBasename()[0]) || str_starts_with($path, 'Cake' . DS) || str_contains($path, 'templates')) {
                continue;
            }
            $classes[] = 'Cake\\' . str_replace(DS, '\\', substr($path, 0, -4));
        }
        $this->assertGreaterThan(250, count($classes));

        foreach ($classes as $class) {
            $global = substr($class, strrpos($class, '\\') + 1);
            $this->assertTrue(
                class_exists($global) || interface_exists($global) || trait_exists($global),
                "$global is not declared",
            );
            $this->assertSame($class, (new ReflectionClass($global))->getName(), "$global is not $class");
        }
    }

    /**
     * Test that the global name is declared as soon as the namespaced class is loaded,
     * so that it works where PHP does not autoload, e.g. in a catch block.
     *
     * @return void
     */
    public function testGlobalNameWithoutAutoloading(): void
    {
        $output = $this->_run(
            '$loaded = class_exists("Cake\\\\Utility\\\\Xml");' . "\n" .
            'echo var_export(class_exists("Xml", false), true), "\n";' . "\n" .
            'try {' . "\n" .
            '    throw new Cake\\Error\\NotFoundException("Kaboom");' . "\n" .
            '} catch (NotFoundException $e) {' . "\n" .
            '    echo get_class($e), "\n";' . "\n" .
            '}' . "\n",
        );
        $this->assertSame("true\nCake\\Error\\NotFoundException\n", $output);
    }

    /**
     * Test that a global name loads the namespaced class when it is used first.
     *
     * @return void
     */
    public function testGlobalNameFirst(): void
    {
        $output = $this->_run(
            'class TestHash extends Hash {}' . "\n" .
            'echo get_parent_class(new TestHash()), "\n";' . "\n" .
            'echo var_export(new TestHash() instanceof Cake\\Utility\\Hash, true), "\n";' . "\n",
        );
        $this->assertSame("Cake\\Utility\\Hash\ntrue\n", $output);
    }

    /**
     * Test that global names are case-insensitive, as class names in PHP.
     *
     * @return void
     */
    public function testGlobalNameCaseInsensitive(): void
    {
        $output = $this->_run(
            'echo get_class(new hash()), "\n";' . "\n" .
            'echo HASH::get(["a" => 1], "a"), "\n";' . "\n" .
            'echo var_export(new HASH() instanceof Hash, true), "\n";' . "\n",
        );
        $this->assertSame("Cake\\Utility\\Hash\n1\ntrue\n", $output);
    }

    /**
     * Runs code in a new PHP process with the autoloader of Composer.
     *
     * @param string $code The code to run
     * @return string The output
     */
    protected function _run(string $code): string
    {
        $this->skipIf(!function_exists('proc_open'), 'proc_open() is not available.');

        $file = TMP . 'tests' . DS . 'class_aliases_' . uniqid() . '.php';
        $autoload = dirname((new ReflectionClass(ClassLoader::class))->getFileName(), 2) . DS . 'autoload.php';
        file_put_contents($file, '<?php' . "\n" . 'require ' . var_export($autoload, true) . ';' . "\n" . $code);

        try {
            $process = proc_open(
                [PHP_BINARY, '-d', 'display_errors=stderr', $file],
                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
                $pipes,
            );
            $output = stream_get_contents($pipes[1]);
            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            proc_close($process);
        } finally {
            unlink($file);
        }
        $this->assertSame('', $errors);

        return $output;
    }
}
