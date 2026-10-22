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

namespace Cake\Test\TestCase\Core;

use Cake\TestSuite\CakeTestCase;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;

/**
 * Tests the global class names of CakePHP 2, declared by src/aliases.php and by the class files.
 */
class ClassAliasesTest extends CakeTestCase
{
    /**
     * Test that the global name of each class loads the namespaced class when it is used first.
     *
     * It runs in its own process, so that the classes are not loaded yet.
     *
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     * @return void
     */
    public function testGlobalNameLoadsTheClass(): void
    {
        $classes = [];
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(CAKE, FilesystemIterator::SKIP_DOTS));
        foreach ($files as $file) {
            $path = substr($file->getPathname(), strlen(CAKE));
            // The class files, by PSR-4; not the functions, the templates or the former paths in Cake/
            if ($file->getExtension() !== 'php' || !ctype_upper($file->getBasename()[0]) || str_starts_with($path, 'Cake' . DS) || str_contains($path, 'templates')) {
                continue;
            }
            $classes[$file->getRealPath()] = 'Cake\\' . str_replace(DS, '\\', substr($path, 0, -4));
        }
        $this->assertGreaterThan(250, count($classes));

        $loadedByGlobalName = 0;
        foreach ($classes as $file => $class) {
            $global = substr($class, strrpos($class, '\\') + 1);
            $loaded = class_exists($class, false) || interface_exists($class, false) || trait_exists($class, false);

            $this->assertTrue(
                class_exists($global) || interface_exists($global) || trait_exists($global),
                "$global is not declared",
            );
            $reflection = new ReflectionClass($global);
            $this->assertSame($class, $reflection->getName(), "$global is not $class");
            $this->assertSame($file, $reflection->getFileName(), "$global is not declared in $file");
            if (!$loaded) {
                $loadedByGlobalName++;
            }
        }
        // Most classes are not loaded by the bootstrap, or by the classes loaded before them
        $this->assertGreaterThan(count($classes) / 2, $loadedByGlobalName);
    }
}
