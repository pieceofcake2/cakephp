<?php
/**
 * FolderTest file
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
 * @package       Cake.Test.Case.Utility
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Utility;

use Cake\TestSuite\CakeTestCase;
use Cake\Utility\File;
use Cake\Utility\Folder;
use InvalidArgumentException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * FolderTest class
 *
 * @package Cake.Test.Case.Utility
 * @coversDefaultClass \Cake\Utility\Folder
 */
class FolderTest extends CakeTestCase
{
    protected static $_tmp = [];

    /**
     * Save the directory names in TMP and make sure default directories exist
     *
     * @return void
     */
    public static function setUpBeforeClass(): void
    {
        $dirs = ['cache', 'logs', 'sessions', 'tests'];
        foreach ($dirs as $dir) {
            new Folder(TMP . $dir, true);
        }

        foreach (scandir(TMP) as $file) {
            if (is_dir(TMP . $file) && !in_array($file, ['.', '..'])) {
                static::$_tmp[] = $file;
            }
        }
    }

    /**
     * setUp clearstatcache() to flush file descriptors.
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        clearstatcache();
    }

    /**
     * Restore the TMP directory to its original state.
     *
     * @return void
     */
    public function tearDown(): void
    {
        $exclude = array_merge(static::$_tmp, ['.', '..']);
        foreach (scandir(TMP) as $dir) {
            if (is_dir(TMP . $dir) && !in_array($dir, $exclude)) {
                $iterator = new RecursiveDirectoryIterator(TMP . $dir);
                foreach (new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST) as $file) {
                    if ($file->isFile() || $file->isLink()) {
                        unlink($file->getPathname());
                    } elseif ($file->isDir() && !in_array($file->getFilename(), ['.', '..'])) {
                        rmdir($file->getPathname());
                    }
                }
                rmdir(TMP . $dir);
            }
        }

        parent::tearDown();
    }

    /**
     * testBasic method
     *
     * @return void
     */
    public function testBasic(): void
    {
        $path = __DIR__;
        $Folder = new Folder($path);

        $result = $Folder->pwd();
        $this->assertEquals($path, $result);

        $result = Folder::addPathElement($path, 'test');
        $expected = $path . DS . 'test';
        $this->assertEquals($expected, $result);

        $result = $Folder->cd(ROOT);
        $expected = ROOT;
        $this->assertEquals($expected, $result);

        $result = $Folder->cd(ROOT . DS . 'non-existent');
        $this->assertFalse($result);
    }

    /**
     * testInPath method
     *
     * @return void
     */
    public function testInPath(): void
    {
        // "/Test/test_app/"
        $basePath = CORE_TESTS . DS . 'test_app' . DS;
        $Base = new Folder($basePath);

        $result = $Base->pwd();
        $this->assertEquals($basePath, $result);

        // is "/" in "/Test/test_app/"
        $result = $Base->inPath(realpath(DS), true);
        $this->assertFalse($result, true);

        // is "/Test/test_app/" in "/Test/test_app/"
        $result = $Base->inPath($basePath, true);
        $this->assertTrue($result);

        // is "/Test/test_app" in "/Test/test_app/"
        $result = $Base->inPath(mb_substr($basePath, 0, -1), true);
        $this->assertTrue($result);

        // is "/Test/test_app/sub" in "/Test/test_app/"
        $result = $Base->inPath($basePath . 'sub', true);
        $this->assertTrue($result);

        // is "/Test" in "/Test/test_app/"
        $result = $Base->inPath(dirname($basePath), true);
        $this->assertFalse($result);

        // is "/Test/other/(...)Test/test_app" in "/Test/test_app/"
        $result = $Base->inPath(TMP . 'tests' . DS . 'other' . DS . $basePath, true);
        $this->assertFalse($result);

        // is "/Test/test_app/" in "/"
        $result = $Base->inPath(realpath(DS));
        $this->assertTrue($result);

        // is "/Test/test_app/" in "/Test/test_app/"
        $result = $Base->inPath($basePath);
        $this->assertTrue($result);

        // is "/Test/test_app/" in "/Test/test_app"
        $result = $Base->inPath(mb_substr($basePath, 0, -1));
        $this->assertTrue($result);

        // is "/Test/test_app/" in "/Test"
        $result = $Base->inPath(dirname($basePath));
        $this->assertTrue($result);

        // is "/Test/test_app/" in "/Test/test_app/sub"
        $result = $Base->inPath($basePath . 'sub');
        $this->assertFalse($result);

        // is "/other/Test/test_app/" in "/Test/test_app/"
        $VirtualBase = new Folder();
        $VirtualBase->path = '/other/Test/test_app';
        $result = $VirtualBase->inPath('/Test/test_app/');
        $this->assertFalse($result);
    }

    /**
     * Data provider for the testInPathInvalidPathArgument test
     *
     * @return array
     */
    public function inPathInvalidPathArgumentDataProvider(): mixed
    {
        return [
            [''],
            ['relative/path/'],
            ['unknown://stream-wrapper'],
        ];
    }

    /**
     * @dataProvider inPathInvalidPathArgumentDataProvider
     * @param string $path
     */
    public function testInPathInvalidPathArgument($path): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('The $path argument is expected to be an absolute path.');
        $Folder = new Folder();
        $Folder->inPath($path);
    }

    /**
     * test creation of single and multiple paths.
     *
     * @return void
     */
    public function testCreation(): void
    {
        $Folder = new Folder(TMP . 'tests');
        $result = $Folder->create(TMP . 'tests' . DS . 'first' . DS . 'second' . DS . 'third');
        $this->assertTrue($result);

        rmdir(TMP . 'tests' . DS . 'first' . DS . 'second' . DS . 'third');
        rmdir(TMP . 'tests' . DS . 'first' . DS . 'second');
        rmdir(TMP . 'tests' . DS . 'first');

        $Folder = new Folder(TMP . 'tests');
        $result = $Folder->create(TMP . 'tests' . DS . 'first');
        $this->assertTrue($result);
        rmdir(TMP . 'tests' . DS . 'first');
    }

    /**
     * test that creation of folders with trailing ds works
     *
     * @return void
     */
    public function testCreateWithTrailingDs(): void
    {
        $Folder = new Folder(TMP);
        $path = TMP . 'tests' . DS . 'trailing' . DS . 'dir' . DS;
        $result = $Folder->create($path);
        $this->assertTrue($result);

        $this->assertTrue(is_dir($path), 'Folder was not made');

        $Folder = new Folder(TMP . 'tests' . DS . 'trailing');
        $this->assertTrue($Folder->delete());
    }

    /**
     * Test that relative paths to create() are added to cwd.
     *
     * @return void
     */
    public function testCreateRelative(): void
    {
        $folder = new Folder(TMP);
        $path = TMP . 'tests' . DS . 'relative-test';
        $result = $folder->create('tests' . DS . 'relative-test');
        $this->assertTrue($result, 'should create');

        $this->assertTrue(is_dir($path), 'Folder was not made');
        $folder = new Folder($path);
        $folder->delete();
    }

    /**
     * test recursive directory create failure.
     *
     * @return void
     */
    public function testRecursiveCreateFailure(): void
    {
        $this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Cant perform operations using permissions on Windows.');

        $path = TMP . 'tests' . DS . 'one';
        mkdir($path);
        chmod($path, '0444');

        $warningTriggered = false;
        $warningMessage = '';
        set_error_handler(function ($errno, $errstr) use (&$warningTriggered, &$warningMessage) {
            $warningTriggered = true;
            $warningMessage = $errstr;

            return true;
        }, E_WARNING | E_USER_WARNING);

        try {
            $Folder = new Folder($path);
            $result = $Folder->create($path . DS . 'two' . DS . 'three');
            $this->assertFalse($result);
        } finally {
            restore_error_handler();
            chmod($path, '0777');
            rmdir($path);
        }

        $this->assertTrue($warningTriggered || $result === false, 'Expected error or false result');
        $this->assertSame('mkdir(): Permission denied', $warningMessage);
    }

    /**
     * testOperations method
     *
     * @return void
     */
    public function testOperations(): void
    {
        $path = CORE_ROOT . DS . 'templates';
        $Folder = new Folder($path);

        $result = is_dir($Folder->pwd());
        $this->assertTrue($result);

        $new = TMP . 'test_folder_new';
        $result = $Folder->create($new);
        $this->assertTrue($result);

        $copy = TMP . 'test_folder_copy';
        $result = $Folder->copy($copy);
        $this->assertTrue($result);

        $copy = TMP . 'test_folder_copy';
        $result = $Folder->copy($copy);
        $this->assertTrue($result);

        $copy = TMP . 'test_folder_copy';
        $result = $Folder->chmod($copy, 0755, false);
        $this->assertTrue($result);

        $result = $Folder->cd($copy);
        $this->assertTrue((bool)$result);

        $mv = TMP . 'test_folder_mv';
        $result = $Folder->move($mv);
        $this->assertTrue($result);

        $mv = TMP . 'test_folder_mv_2';
        $result = $Folder->move($mv);
        $this->assertTrue($result);

        $result = $Folder->delete($new);
        $this->assertTrue($result);

        $result = $Folder->delete($mv);
        $this->assertTrue($result);

        $result = $Folder->delete($mv);
        $this->assertTrue($result);

        $new = WWW_ROOT . 'index.php';
        $result = $Folder->create($new);
        $this->assertFalse($result);

        $expected = $new . ' is a file';
        $result = $Folder->errors();
        $this->assertEquals($expected, $result[0]);

        $new = TMP . 'test_folder_new';
        $result = $Folder->create($new);
        $this->assertTrue($result);

        $result = $Folder->cd($new);
        $this->assertTrue((bool)$result);

        $result = $Folder->delete();
        $this->assertTrue($result);

        $Folder = new Folder('non-existent');
        $result = $Folder->pwd();
        $this->assertNull($result);
    }

    /**
     * testChmod method
     *
     * @return void
     */
    public function testChmod(): void
    {
        $this->skipIf(DIRECTORY_SEPARATOR === '\\', 'Folder permissions tests not supported on Windows.');

        $path = TMP;
        $Folder = new Folder($path);

        $subdir = 'test_folder_new';
        $new = TMP . $subdir;

        $this->assertTrue($Folder->create($new));
        $this->assertTrue($Folder->create($new . DS . 'test1'));
        $this->assertTrue($Folder->create($new . DS . 'test2'));

        $filePath = $new . DS . 'test1.php';
        $File = new File($filePath);
        $this->assertTrue($File->create());

        $filePath = $new . DS . 'skip_me.php';
        $File = new File($filePath);
        $this->assertTrue($File->create());

        $this->assertTrue($Folder->chmod($new, 0755, true));
        $perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
        $this->assertEquals('0755', $perms);

        $this->assertTrue($Folder->chmod($new, 0744, true, ['skip_me.php', 'test2']));

        $perms = substr(sprintf('%o', fileperms($new . DS . 'test2')), -4);
        $this->assertEquals('0755', $perms);

        $perms = substr(sprintf('%o', fileperms($new . DS . 'test1')), -4);
        $this->assertEquals('0744', $perms);

        $Folder->delete($new);
    }

    /**
     * testRealPathForWebroot method
     *
     * @return void
     */
    public function testRealPathForWebroot(): void
    {
        $Folder = new Folder('files/');
        $this->assertEquals(realpath('files/'), $Folder->path);
    }

    /**
     * testZeroAsDirectory method
     *
     * @return void
     */
    public function testZeroAsDirectory(): void
    {
        $Folder = new Folder(TMP);
        $new = TMP . '0';
        $this->assertTrue($Folder->create($new));

        $result = $Folder->read(true, true);
        $expected = ['0', 'cache', 'logs', 'sessions', 'tests'];
        $this->assertEquals($expected, $result[0]);

        $result = $Folder->read(true, ['logs']);
        $expected = ['0', 'cache', 'sessions', 'tests'];
        $this->assertEquals($expected, $result[0]);

        $result = $Folder->delete($new);
        $this->assertTrue($result);
    }

    /**
     * test Adding path elements to a path
     *
     * @return void
     */
    public function testAddPathElement(): void
    {
        $expected = DS . 'some' . DS . 'dir' . DS . 'another_path';

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', 'another_path');
        $this->assertEquals($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, 'another_path');
        $this->assertEquals($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', ['another_path']);
        $this->assertEquals($expected, $result);

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir' . DS, ['another_path']);
        $this->assertEquals($expected, $result);

        $expected = DS . 'some' . DS . 'dir' . DS . 'another_path' . DS . 'and' . DS . 'another';

        $result = Folder::addPathElement(DS . 'some' . DS . 'dir', ['another_path', 'and', 'another']);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderRead method
     *
     * @return void
     */
    public function testFolderRead(): void
    {
        $Folder = new Folder(TMP);

        $expected = ['cache', 'logs', 'sessions', 'tests'];
        $result = $Folder->read(true, true);
        $this->assertEquals($expected, $result[0]);

        $Folder->path = TMP . 'non-existent';
        $expected = [[], []];
        $result = $Folder->read(true, true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderReadWithHiddenFiles method
     *
     * @return void
     */
    public function testFolderReadWithHiddenFiles(): void
    {
        $this->skipIf(!is_writable(TMP), 'Cant test Folder::read with hidden files unless the tmp folder is writable.');

        $Folder = new Folder(TMP . 'folder_tree_hidden', true, 0777);
        mkdir($Folder->path . DS . '.svn');
        mkdir($Folder->path . DS . 'some_folder');
        touch($Folder->path . DS . 'not_hidden.txt');
        touch($Folder->path . DS . '.hidden.txt');

        $expected = [
            ['some_folder'],
            ['not_hidden.txt'],
        ];
        $result = $Folder->read(true, true);
        $this->assertEquals($expected, $result);

        $expected = [
            [
                '.svn',
                'some_folder',
            ],
            [
                '.hidden.txt',
                'not_hidden.txt',
            ],
        ];
        $result = $Folder->read(true);
        $this->assertEquals($expected, $result);
    }

    /**
     * testFolderTree method
     *
     * @return void
     */
    public function testFolderTree(): void
    {
        $Folder = new Folder();
        $expected = [
            [
                CORE_ROOT . DS . 'config',
                CORE_ROOT . DS . 'config' . DS . 'unicode',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding',
            ],
            [
                CORE_ROOT . DS . 'config' . DS . 'config.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0080_00ff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0100_017f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0180_024F.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0250_02af.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0370_03ff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0400_04ff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0500_052f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '0530_058f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '1e00_1eff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '1f00_1fff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '2100_214f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '2150_218f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '2460_24ff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '2c00_2c5f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '2c60_2c7f.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . '2c80_2cff.php',
                CORE_ROOT . DS . 'config' . DS . 'unicode' . DS . 'casefolding' . DS . 'ff00_ffef.php',
            ],
        ];

        $result = $Folder->tree(CORE_ROOT . DS . 'config', false);
        $this->assertSame([], array_diff($expected[0], $result[0]));
        $this->assertSame([], array_diff($result[0], $expected[0]));

        $result = $Folder->tree(CORE_ROOT . DS . 'config', false, 'dir');
        $this->assertSame([], array_diff($expected[0], $result));
        $this->assertSame([], array_diff($expected[0], $result));

        $result = $Folder->tree(CORE_ROOT . DS . 'config', false, 'files');
        $this->assertSame([], array_diff($expected[1], $result));
        $this->assertSame([], array_diff($expected[1], $result));
    }

    /**
     * testFolderTreeWithHiddenFiles method
     *
     * @return void
     */
    public function testFolderTreeWithHiddenFiles(): void
    {
        $this->skipIf(!is_writable(TMP), 'Can\'t test Folder::tree with hidden files unless the tmp folder is writable.');

        $Folder = new Folder(TMP . 'folder_tree_hidden', true, 0777);
        mkdir($Folder->path . DS . '.svn', 0777, true);
        touch($Folder->path . DS . '.svn' . DS . 'InHiddenFolder.php');
        mkdir($Folder->path . DS . '.svn' . DS . 'inhiddenfolder');
        touch($Folder->path . DS . '.svn' . DS . 'inhiddenfolder' . DS . 'NestedInHiddenFolder.php');
        touch($Folder->path . DS . 'not_hidden.txt');
        touch($Folder->path . DS . '.hidden.txt');
        mkdir($Folder->path . DS . 'visible_folder' . DS . '.git', 0777, true);

        $expected = [
            [
                $Folder->path,
                $Folder->path . DS . 'visible_folder',
            ],
            [
                $Folder->path . DS . 'not_hidden.txt',
            ],
        ];

        $result = $Folder->tree(null, true);
        $this->assertEquals($expected, $result);

        $result = $Folder->tree(null, ['.']);
        $this->assertEquals($expected, $result);

        $expected = [
            [
                $Folder->path,
                $Folder->path . DS . 'visible_folder',
                $Folder->path . DS . 'visible_folder' . DS . '.git',
                $Folder->path . DS . '.svn',
                $Folder->path . DS . '.svn' . DS . 'inhiddenfolder',
            ],
            [
                $Folder->path . DS . 'not_hidden.txt',
                $Folder->path . DS . '.hidden.txt',
                $Folder->path . DS . '.svn' . DS . 'inhiddenfolder' . DS . 'NestedInHiddenFolder.php',
                $Folder->path . DS . '.svn' . DS . 'InHiddenFolder.php',
            ],
        ];

        $result = $Folder->tree(null, false);
        sort($result[0]);
        sort($expected[0]);
        sort($result[1]);
        sort($expected[1]);
        $this->assertEquals($expected, $result);

        $Folder->delete();
    }

    /**
     * testWindowsPath method
     *
     * @return void
     */
    public function testWindowsPath(): void
    {
        $this->assertFalse(Folder::isWindowsPath('0:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('C:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('d:\\cake\\is\\awesome'));
        $this->assertTrue(Folder::isWindowsPath('\\\\vmware-host\\Shared Folders\\file'));
    }

    /**
     * testIsAbsolute method
     *
     * @return void
     */
    public function testIsAbsolute(): void
    {
        $this->assertFalse(Folder::isAbsolute('path/to/file'));
        $this->assertFalse(Folder::isAbsolute('cake/'));
        $this->assertFalse(Folder::isAbsolute('path\\to\\file'));
        $this->assertFalse(Folder::isAbsolute('0:\\path\\to\\file'));
        $this->assertFalse(Folder::isAbsolute('\\path/to/file'));
        $this->assertFalse(Folder::isAbsolute('\\path\\to\\file'));
        $this->assertFalse(Folder::isAbsolute('notRegisteredStreamWrapper://example'));
        $this->assertFalse(Folder::isAbsolute('://example'));

        $this->assertTrue(Folder::isAbsolute('/usr/local'));
        $this->assertTrue(Folder::isAbsolute('//path/to/file'));
        $this->assertTrue(Folder::isAbsolute('C:\\cake'));
        $this->assertTrue(Folder::isAbsolute('C:\\path\\to\\file'));
        $this->assertTrue(Folder::isAbsolute('d:\\path\\to\\file'));
        $this->assertTrue(Folder::isAbsolute('\\\\vmware-host\\Shared Folders\\file'));
        $this->assertTrue(Folder::isAbsolute('http://www.example.com'));
    }

    /**
     * Stream wrapper schemes may contain digits, "+", "-" and "." after the first letter (e.g. s3://).
     *
     * @return void
     */
    public function testIsAbsoluteWithSchemeContainingDigits(): void
    {
        $this->assertTrue(stream_wrapper_register('s3test', FolderTestStreamWrapper::class));
        $this->assertTrue(stream_wrapper_register('x-foo.bar+2', FolderTestStreamWrapper::class));
        try {
            $this->assertTrue(Folder::isAbsolute('s3test://bucket/path'));
            $this->assertTrue(Folder::isRegisteredStreamWrapper('s3test://bucket/path'));
            $this->assertTrue(Folder::isAbsolute('x-foo.bar+2://path'));
            $this->assertFalse(Folder::isAbsolute('3test://bucket/path'));
            $this->assertFalse(Folder::isAbsolute('s4test://bucket/path'));
        } finally {
            stream_wrapper_unregister('s3test');
            stream_wrapper_unregister('x-foo.bar+2');
        }
    }

    /**
     * testIsSlashTerm method
     *
     * @return void
     */
    public function testIsSlashTerm(): void
    {
        $this->assertFalse(Folder::isSlashTerm('cake'));

        $this->assertTrue(Folder::isSlashTerm('C:\\cake\\'));
        $this->assertTrue(Folder::isSlashTerm('/usr/local/'));
    }

    /**
     * testStatic method
     *
     * @return void
     */
    public function testSlashTerm(): void
    {
        $result = Folder::slashTerm('/path/to/file');
        $this->assertEquals('/path/to/file/', $result);
    }

    /**
     * testNormalizePath method
     *
     * @return void
     */
    public function testNormalizePath(): void
    {
        $path = '/path/to/file';
        $result = Folder::normalizePath($path);
        $this->assertEquals('/', $result);

        $path = '\\path\\\to\\\file';
        $result = Folder::normalizePath($path);
        $this->assertEquals('/', $result);

        $path = 'C:\\path\\to\\file';
        $result = Folder::normalizePath($path);
        $this->assertEquals('\\', $result);
    }

    /**
     * correctSlashFor method
     *
     * @return void
     */
    public function testCorrectSlashFor(): void
    {
        $path = '/path/to/file';
        $result = Folder::correctSlashFor($path);
        $this->assertEquals('/', $result);

        $path = '\\path\\to\\file';
        $result = Folder::correctSlashFor($path);
        $this->assertEquals('/', $result);

        $path = 'C:\\path\to\\file';
        $result = Folder::correctSlashFor($path);
        $this->assertEquals('\\', $result);
    }

    /**
     * testInCakePath method
     *
     * @return void
     */
    public function testInCakePath(): void
    {
        $Folder = new Folder();
        $Folder->cd(ROOT);
        $path = 'C:\\path\\to\\file';
        $result = $Folder->inCakePath($path);
        $this->assertFalse($result);

        $path = ROOT;
        $Folder->cd(ROOT);
        $result = $Folder->inCakePath($path);
        $this->assertFalse($result);

        $path = DS . 'config';
        $Folder->cd(dirname(CAKE_CORE_INCLUDE_PATH) . DS . 'config');
        $result = $Folder->inCakePath($path);
        $this->assertTrue($result);
    }

    /**
     * testFind method
     *
     * @return void
     */
    public function testFind(): void
    {
        $Folder = new Folder();
        $Folder->cd(CORE_ROOT . DS . 'config');
        $result = $Folder->find();
        $expected = ['config.php'];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->find('.*', true);
        $expected = ['config.php', 'core.php', 'define.php', 'init.php', 'routes.php'];
        $this->assertSame($expected, $result);

        $result = $Folder->find('.*\.php');
        $expected = ['config.php'];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->find('.*\.php', true);
        $expected = ['config.php', 'core.php', 'define.php', 'init.php', 'routes.php'];
        $this->assertSame($expected, $result);

        $result = $Folder->find('.*ig\.php');
        $expected = ['config.php'];
        $this->assertSame($expected, $result);

        $result = $Folder->find('config\.php');
        $expected = ['config.php'];
        $this->assertSame($expected, $result);

        $Folder->cd(TMP);
        $File = new File($Folder->pwd() . DS . 'paths.php', true);
        $Folder->create($Folder->pwd() . DS . 'testme');
        $Folder->cd('testme');
        $result = $Folder->find('paths\.php');
        $expected = [];
        $this->assertSame($expected, $result);

        $Folder->cd($Folder->pwd() . '/..');
        $result = $Folder->find('paths\.php');
        $expected = ['paths.php'];
        $this->assertSame($expected, $result);

        $Folder->cd(TMP);
        $Folder->delete($Folder->pwd() . DS . 'testme');
        $File->delete();
    }

    /**
     * testFindRecursive method
     *
     * @return void
     */
    public function testFindRecursive(): void
    {
        $Folder = new Folder();
        $Folder->cd(CORE_ROOT . DS . 'config' . DS);
        $result = $Folder->findRecursive('(config|paths)\.php');
        $expected = [
            CORE_ROOT . DS . 'config' . DS . 'config.php',
        ];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->findRecursive('(config|paths)\.php', true);
        $expected = [
            CORE_ROOT . DS . 'config' . DS . 'config.php',
        ];
        $this->assertSame($expected, $result);

        $Folder->cd(TMP);
        $Folder->create($Folder->pwd() . DS . 'testme');
        $Folder->cd('testme');
        $File = new File($Folder->pwd() . DS . 'paths.php');
        $File->create();
        $Folder->cd(TMP . 'sessions');
        $result = $Folder->findRecursive('paths\.php');
        $expected = [];
        $this->assertSame($expected, $result);

        $Folder->cd(TMP . 'testme');
        $File = new File($Folder->pwd() . DS . 'my.php');
        $File->create();
        $Folder->cd($Folder->pwd() . '/../..');

        $result = $Folder->findRecursive('(paths|my)\.php');
        $expected = [
            TMP . 'testme' . DS . 'my.php',
            TMP . 'testme' . DS . 'paths.php',
        ];
        $this->assertSame(array_diff($expected, $result), []);
        $this->assertSame(array_diff($expected, $result), []);

        $result = $Folder->findRecursive('(paths|my)\.php', true);
        $expected = [
            TMP . 'testme' . DS . 'my.php',
            TMP . 'testme' . DS . 'paths.php',
        ];
        $this->assertSame($expected, $result);

        $Folder->cd(CORE_ROOT . DS . 'config');
        $Folder->cd(TMP);
        $Folder->delete($Folder->pwd() . DS . 'testme');
        $File->delete();
    }

    /**
     * testConstructWithNonExistentPath method
     *
     * @return void
     */
    public function testConstructWithNonExistentPath(): void
    {
        $Folder = new Folder(TMP . 'config_non_existent', true);
        $this->assertTrue(is_dir(TMP . 'config_non_existent'));
        $Folder->cd(TMP);
        $Folder->delete($Folder->pwd() . 'config_non_existent');
    }

    /**
     * testDirSize method
     *
     * @return void
     */
    public function testDirSize(): void
    {
        $Folder = new Folder(TMP . 'config_non_existent', true);
        $this->assertEquals(0, $Folder->dirSize());

        $File = new File($Folder->pwd() . DS . 'my.php', true, 0777);
        $File->create();
        $File->write('something here');
        $File->close();
        $this->assertEquals(14, $Folder->dirSize());

        $Folder->cd(TMP);
        $Folder->delete($Folder->pwd() . 'config_non_existent');
    }

    /**
     * test that errors and messages can be resetted
     *
     * @return void
     */
    public function testReset(): void
    {
        $path = TMP . 'folder_delete_test';
        @mkdir($path); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $folder = $path . DS . 'sub';
        @mkdir($folder); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        $file = $folder . DS . 'file';
        touch($file);

        chmod($folder, 0555);
        chmod($file, 0444);

        $Folder = new Folder($folder);
        $return = $Folder->delete();
        $this->assertFalse($return);

        $messages = $Folder->messages();
        $errors = $Folder->errors();
        $expected = [
            $file . ' NOT removed',
            $folder . ' NOT removed',
        ];
        sort($expected);
        sort($errors);
        $this->assertEmpty($messages);
        $this->assertEquals($expected, $errors);

        chmod($file, 0644);
        chmod($folder, 0755);

        $return = $Folder->delete();
        $this->assertTrue($return);

        $messages = $Folder->messages();
        $errors = $Folder->errors();
        $expected = [
            $file . ' removed',
            $folder . ' removed',
        ];
        sort($expected);
        sort($messages);
        $this->assertEmpty($errors);
        $this->assertEquals($expected, $messages);
    }

    /**
     * testDelete method
     *
     * @return void
     */
    public function testDelete(): void
    {
        $path = TMP . 'folder_delete_test';
        @mkdir($path); // phpcs:ignore Generic.PHP.NoSilencedErrors.Discouraged
        touch($path . DS . 'file_1');
        mkdir($path . DS . 'level_1_1');
        touch($path . DS . 'level_1_1' . DS . 'file_1_1');
        mkdir($path . DS . 'level_1_1' . DS . 'level_2_1');
        touch($path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_1');
        touch($path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_2');
        mkdir($path . DS . 'level_1_1' . DS . 'level_2_2');

        $Folder = new Folder($path, true);
        $return = $Folder->delete();
        $this->assertTrue($return);

        $messages = $Folder->messages();
        $errors = $Folder->errors();
        $this->assertEquals([], $errors);

        $expected = [
            $path . DS . 'file_1 removed',
            $path . DS . 'level_1_1' . DS . 'file_1_1 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_1 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_1' . DS . 'file_2_2 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_1 removed',
            $path . DS . 'level_1_1' . DS . 'level_2_2 removed',
            $path . DS . 'level_1_1 removed',
            $path . ' removed',
        ];
        sort($expected);
        sort($messages);
        $this->assertEquals($expected, $messages);
    }

    /**
     * testCopy method
     *
     * Verify that subdirectories existing in both destination and source directory
     * are merged recursively.
     *
     * @return void
     */
    public function testCopy(): void
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * testCopyWithMerge method
     *
     * Verify that subdirectories existing in both destination and source directory
     * are merged recursively.
     *
     * @return void
     */
    public function testCopyWithMerge(): void
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy($folderThree);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::MERGE]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * testCopyWithSkip method
     *
     * Verify that directories and files are copied recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are skipped and not merged or overwritten.
     *
     * @return void
     */
    public function testCopyWithSkip(): void
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $Folder->delete();

        new Folder($folderTwo, true);
        new Folder($folderTwo . DS . 'folderB', true);
        file_put_contents($folderTwo . DS . 'file2.php', 'touched');
        file_put_contents($folderTwo . DS . 'folderB' . DS . 'fileB.php', 'untouched');

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertEquals('touched', file_get_contents($folderThree . DS . 'file2.php'));
        $this->assertEquals('untouched', file_get_contents($folderThree . DS . 'folderB' . DS . 'fileB.php'));

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * Test that SKIP mode skips files too.
     *
     * @return void
     */
    public function testCopyWithSkipFileSkipped(): void
    {
        $path = TMP . 'folder_test';
        $folderOne = $path . DS . 'folder1';
        $folderTwo = $path . DS . 'folder2';

        new Folder($path, true);
        new Folder($folderOne, true);
        new Folder($folderTwo, true);
        file_put_contents($folderOne . DS . 'fileA.txt', 'Folder One File');
        file_put_contents($folderTwo . DS . 'fileA.txt', 'Folder Two File');

        $Folder = new Folder($folderOne);
        $result = $Folder->copy(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertEquals('Folder Two File', file_get_contents($folderTwo . DS . 'fileA.txt'));
    }

    /**
     * testCopyWithOverwrite
     *
     * Verify that subdirectories existing in both destination and source directory
     * are overwritten/replaced recursively.
     *
     * @return void
     */
    public function testCopyWithOverwrite(): void
    {
        extract($this->_setupFilesystem());

        $Folder = new Folder($folderOne);
        $Folder->copy(['to' => $folderThree, 'scheme' => Folder::OVERWRITE]);

        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderTwo);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::OVERWRITE]);
        $this->assertTrue($result);

        $this->assertTrue(file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));

        $Folder = new Folder($folderOne);
        unlink($fileOneA);
        $result = $Folder->copy(['to' => $folderThree, 'scheme' => Folder::OVERWRITE]);
        $this->assertTrue($result);

        $this->assertTrue(file_exists($folderThree . DS . 'file1.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'file2.php'));
        $this->assertTrue(!file_exists($folderThree . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertTrue(file_exists($folderThree . DS . 'folderB' . DS . 'fileB.php'));

        $Folder = new Folder($path);
        $Folder->delete();
    }

    /**
     * Setup filesystem for copy tests
     * $path: folder_test/
     * - folder1/file1.php
     * - folder1/folderA/fileA.php
     * - folder2/file2.php
     * - folder2/folderB/fileB.php
     * - folder3/
     *
     * @return array Filenames to extract in the test methods
     */
    protected function _setupFilesystem(): mixed
    {
        $path = TMP . 'folder_test';

        $folderOne = $path . DS . 'folder1';
        $folderOneA = $folderOne . DS . 'folderA';
        $folderTwo = $path . DS . 'folder2';
        $folderTwoB = $folderTwo . DS . 'folderB';
        $folderThree = $path . DS . 'folder3';

        $fileOne = $folderOne . DS . 'file1.php';
        $fileTwo = $folderTwo . DS . 'file2.php';
        $fileOneA = $folderOneA . DS . 'fileA.php';
        $fileTwoB = $folderTwoB . DS . 'fileB.php';

        new Folder($path, true);
        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        new Folder($folderTwoB, true);
        new Folder($folderThree, true);
        touch($fileOne);
        touch($fileTwo);
        touch($fileOneA);
        touch($fileTwoB);

        return compact(
            'path',
            'folderOne',
            'folderOneA',
            'folderTwo',
            'folderTwoB',
            'folderThree',
            'fileOne',
            'fileOneA',
            'fileTwo',
            'fileTwoB',
        );
    }

    /**
     * testMove method
     *
     * Verify that directories and files are moved recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are merged recursively.
     *
     * @return void
     */
    public function testMove(): void
    {
        extract($this->_setupFilesystem());

        $folder = new Folder($folderOne);
        $result = $folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(is_dir($folderTwo . DS . 'folderB'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderB' . DS . 'fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA'));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $folder = new Folder($folderTwo);
        $folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        touch($fileOne);
        touch($fileOneA);

        $folder = new Folder($folderOne);
        $result = $folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(is_dir($folderTwo . DS . 'folderA'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $folder = new Folder($folderTwo);
        $folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        new Folder($folderTwoB, true);
        touch($fileOne);
        touch($fileOneA);
        new Folder($folderOne . DS . 'folderB', true);
        touch($folderOne . DS . 'folderB' . DS . 'fileB.php');
        file_put_contents($folderTwoB . DS . 'fileB.php', 'untouched');

        $folder = new Folder($folderOne);
        $result = $folder->move($folderTwo);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertEquals('', file_get_contents($folderTwoB . DS . 'fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $folder = new Folder($path);
        $folder->delete();
    }

    /**
     * testMoveWithSkip method
     *
     * Verify that directories and files are moved recursively
     * even if the destination directory already exists.
     * Subdirectories existing in both destination and source directory
     * are skipped and not merged or overwritten.
     *
     * @return void
     */
    public function testMoveWithSkip(): void
    {
        extract($this->_setupFilesystem());

        $folder = new Folder($folderOne);
        $result = $folder->move(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(is_dir($folderTwo . DS . 'folderB'));
        $this->assertTrue(file_exists($folderTwoB . DS . 'fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $folder = new Folder($folderTwo);
        $folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        touch($fileOne);
        touch($fileOneA);

        $folder = new Folder($folderOne);
        $result = $folder->move(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertTrue(is_dir($folderTwo . DS . 'folderA'));
        $this->assertTrue(file_exists($folderTwo . DS . 'folderA' . DS . 'fileA.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $folder = new Folder($folderTwo);
        $folder->delete();

        new Folder($folderOne, true);
        new Folder($folderOneA, true);
        new Folder($folderTwo, true);
        new Folder($folderTwoB, true);
        touch($fileOne);
        touch($fileOneA);
        file_put_contents($folderTwoB . DS . 'fileB.php', 'untouched');

        $folder = new Folder($folderOne);
        $result = $folder->move(['to' => $folderTwo, 'scheme' => Folder::SKIP]);
        $this->assertTrue($result);
        $this->assertTrue(file_exists($folderTwo . DS . 'file1.php'));
        $this->assertEquals('untouched', file_get_contents($folderTwoB . DS . 'fileB.php'));
        $this->assertFalse(file_exists($fileOne));
        $this->assertFalse(file_exists($folderOneA));
        $this->assertFalse(file_exists($fileOneA));

        $folder = new Folder($path);
        $folder->delete();
    }

    /**
     * testSortByTime method
     *
     * Verify that the order using modified time is correct.
     *
     * @return void
     */
    public function testSortByTime(): void
    {
        $folder = new Folder(TMP . 'test_sort_by_time', true);

        $time = $this->_createFileChangedAfter($folder->pwd() . DS . 'file_2.tmp', 0);
        $this->_createFileChangedAfter($folder->pwd() . DS . 'file_1.tmp', $time);

        $expected = ['file_2.tmp', 'file_1.tmp'];
        $result = $folder->find('.*', Folder::SORT_TIME);
        $this->assertSame($expected, $result);

        $folder->delete();
    }

    /**
     * testSortByTime2 method
     *
     * Verify that the sort order using modified time is correct.
     *
     * @return void
     */
    public function testSortByTime2(): void
    {
        $folder = new Folder(TMP . 'test_sort_by_time2', true);

        $time = $this->_createFileChangedAfter($folder->pwd() . DS . 'c.txt', 0);
        $time = $this->_createFileChangedAfter($folder->pwd() . DS . 'a.txt', $time);
        $this->_createFileChangedAfter($folder->pwd() . DS . 'b.txt', $time);

        $expected = ['c.txt', 'a.txt', 'b.txt'];
        $result = $folder->find('.*', Folder::SORT_TIME);
        $this->assertSame($expected, $result);

        $folder->delete();
    }

    /**
     * Verify that the sort order using name is correct.
     *
     * @return void
     */
    public function testSortByName(): void
    {
        $Folder = new Folder(TMP . 'test_sort_by_name', true);

        $fileA = new File($Folder->pwd() . DS . 'a.txt');
        $fileA->create();

        $fileC = new File($Folder->pwd() . DS . 'c.txt');
        $fileC->create();

        sleep(1);

        $fileB = new File($Folder->pwd() . DS . 'b.txt');
        $fileB->create();

        $expected = ['a.txt', 'b.txt', 'c.txt'];
        $result = $Folder->find('.*', Folder::SORT_NAME);
        $this->assertSame($expected, $result);

        $Folder->delete();
    }

    /**
     * Creates a file whose change time is later than the given one.
     *
     * Folder::SORT_TIME sorts by the change time, in seconds, which touch() cannot set. Files
     * created in the same second would sort by name, so this waits until the file system gives
     * the file a later change time; touching the file sets it to the current time.
     *
     * @param string $path The path of the file.
     * @param int $after The change time that the file must be later than.
     * @return int The change time of the file.
     */
    protected function _createFileChangedAfter(string $path, int $after): int
    {
        $this->assertTrue((new File($path))->create(), "Could not create $path.");

        $deadline = microtime(true) + 5;
        clearstatcache(true, $path);
        while (filectime($path) <= $after) {
            $this->assertLessThan($deadline, microtime(true), "The change time of $path is not later than $after.");
            usleep(50000);
            touch($path);
            clearstatcache(true, $path);
        }

        return filectime($path);
    }

    /**
     * Returns a path under TMP that is unique to this process.
     *
     * @param string $name The name of the path.
     * @return string
     */
    protected function _tmpPath(string $name): string
    {
        return TMP . $name . '_' . getmypid();
    }

    /**
     * Deletes a folder that a test created, if it is still there.
     *
     * @param string $path The folder.
     * @return void
     */
    protected function _cleanUp(string $path): void
    {
        if (is_dir($path)) {
            chmod($path, 0755);
            (new Folder($path))->delete();
        }
    }

    /**
     * A Folder on a path that does not exist has no working directory: read(), findRecursive(),
     * copy() and delete() do nothing and report it.
     *
     * @return void
     */
    public function testOperationsWithoutWorkingDirectory(): void
    {
        $missing = $this->_tmpPath('folder_test_missing');
        $copy = $this->_tmpPath('folder_test_missing_copy');
        try {
            $folder = new Folder($missing);
            $this->assertNull($folder->pwd());

            $this->assertSame([[], []], $folder->read());
            $this->assertSame([], $folder->findRecursive());
            $this->assertFalse($folder->copy($copy));
            $this->assertFalse($folder->delete());
            $this->assertFileDoesNotExist($missing);
            $this->assertFileDoesNotExist($copy);
        } finally {
            $this->_cleanUp($missing);
            $this->_cleanUp($copy);
        }
    }

    /**
     * read() with $fullPath returns the full paths of the directories and the files.
     *
     * @return void
     */
    public function testReadWithFullPath(): void
    {
        $path = $this->_tmpPath('folder_test_read_full');
        try {
            $folder = new Folder($path, true);
            mkdir($path . DS . 'sub');
            file_put_contents($path . DS . 'file.txt', 'x');

            $result = $folder->read(true, false, true);
            $this->assertSame([[$path . DS . 'sub'], [$path . DS . 'file.txt']], $result);
        } finally {
            $this->_cleanUp($path);
        }
    }

    /**
     * tree() of a path that does not exist returns empty lists of the requested type.
     *
     * @return void
     */
    public function testTreeOfMissingPath(): void
    {
        $folder = new Folder(TMP);
        $missing = $this->_tmpPath('folder_test_missing_tree');

        $this->assertSame([[], []], $folder->tree($missing));
        $this->assertSame([], $folder->tree($missing, false, 'dir'));
        $this->assertSame([], $folder->tree($missing, false, 'file'));
    }

    /**
     * chmod() without a mode uses the mode of the Folder, and fails on a path that is
     * not a directory without changing the mode of the file.
     *
     * @return void
     */
    public function testChmodWithDefaultModeAndOnFile(): void
    {
        $path = $this->_tmpPath('folder_test_chmod_default');
        try {
            $folder = new Folder($path, true, 0700);
            chmod($path, 0755);
            file_put_contents($path . DS . 'file.txt', 'x');
            chmod($path . DS . 'file.txt', 0644);
            $folder->messages();

            $this->assertTrue($folder->chmod($path, false, false));
            $this->assertSame('0700', substr(sprintf('%o', fileperms($path)), -4));
            $this->assertSame([$path . ' changed to 448'], $folder->messages());

            $this->assertFalse($folder->chmod($path . DS . 'file.txt', 0600));
            $this->assertSame([], $folder->errors());
            clearstatcache();
            $this->assertSame('0644', substr(sprintf('%o', fileperms($path . DS . 'file.txt')), -4));
        } finally {
            $this->_cleanUp($path);
        }
    }

    /**
     * dirsize() adds up the sizes of the files in the subdirectories too.
     *
     * @return void
     */
    public function testDirSizeWithSubdirectories(): void
    {
        $path = $this->_tmpPath('folder_test_dirsize');
        try {
            $folder = new Folder($path, true);
            mkdir($path . DS . 'sub' . DS . 'deeper', 0777, true);
            file_put_contents($path . DS . 'a.txt', 'abc');
            file_put_contents($path . DS . 'sub' . DS . 'b.txt', 'defgh');
            file_put_contents($path . DS . 'sub' . DS . 'deeper' . DS . 'c.txt', 'ij');

            $this->assertSame(10, $folder->dirsize());
        } finally {
            $this->_cleanUp($path);
        }
    }

    /**
     * copy() fails when the source directory does not exist, and when the destination
     * cannot be created, and says why.
     *
     * @return void
     */
    public function testCopyErrors(): void
    {
        $path = $this->_tmpPath('folder_test_copy_errors');
        try {
            $folder = new Folder($path, true);
            file_put_contents($path . DS . 'file.txt', 'x');

            $missing = $path . DS . 'missing';
            $this->assertFalse($folder->copy(['from' => $missing, 'to' => $path . DS . 'to']));
            $this->assertSame([$missing . ' not found'], $folder->errors());
            $this->assertFileDoesNotExist($path . DS . 'to');

            $folder = new Folder($path);
            $target = $path . DS . 'file.txt' . DS . 'target';
            $this->assertFalse($folder->copy($target));
            $this->assertSame([
                $path . DS . 'file.txt is a file',
                $target . ' not writable',
            ], $folder->errors());
        } finally {
            $this->_cleanUp($path);
        }
    }

    /**
     * move() keeps the source when the copy fails.
     *
     * @return void
     */
    public function testMoveKeepsSourceWhenCopyFails(): void
    {
        $path = $this->_tmpPath('folder_test_move_fails');
        try {
            new Folder($path . DS . 'from', true);
            file_put_contents($path . DS . 'from' . DS . 'keep.txt', 'keep');
            file_put_contents($path . DS . 'file.txt', 'x');

            $folder = new Folder($path . DS . 'from');
            $this->assertFalse($folder->move($path . DS . 'file.txt' . DS . 'target'));
            $this->assertSame('keep', file_get_contents($path . DS . 'from' . DS . 'keep.txt'));
        } finally {
            $this->_cleanUp($path);
        }
    }

    /**
     * realpath() resolves ".." in an absolute path, returns false for an absolute path
     * that goes above the root, and cd() does not change to such a path.
     *
     * @return void
     */
    public function testRealpathAboveRoot(): void
    {
        $folder = new Folder(TMP);

        $this->assertFalse($folder->realpath(DS . '..' . DS . 'etc'));
        $this->assertSame(DS . 'a' . DS . 'c' . DS, $folder->realpath(DS . 'a' . DS . 'b' . DS . '..' . DS . 'c'));
        $this->assertFalse($folder->cd(DS . '..' . DS . 'etc'));
        $this->assertSame(TMP, Folder::slashTerm($folder->pwd()));
    }

    /**
     * realpath() resolves a relative path with ".." from the working directory, so it
     * can go above the working directory, and cd() changes to a sibling directory.
     *
     * @return void
     */
    public function testRealpathOfRelativePathWithParent(): void
    {
        $folder = new Folder(TMP);
        $this->assertSame(
            dirname(TMP) . DS . 'b' . DS,
            $folder->realpath('a' . DS . '..' . DS . '..' . DS . 'b'),
        );
        $this->assertSame(TMP . 'y' . DS, $folder->realpath('x' . DS . '..' . DS . 'y'));

        $path = $this->_tmpPath('folder_test_realpath');
        try {
            mkdir($path . DS . 'child', 0777, true);
            mkdir($path . DS . 'sibling');
            $folder = new Folder($path . DS . 'child');

            $this->assertSame($path . DS . 'sibling' . DS, $folder->cd('..' . DS . 'sibling'));
            $this->assertSame($path . DS . 'sibling' . DS, $folder->pwd());
        } finally {
            $this->_cleanUp($path);
        }
    }
}

/**
 * Stream wrapper used to register custom schemes in FolderTest.
 */
class FolderTestStreamWrapper
{
    /**
     * @var resource|null
     */
    public $context;
}
