<?php
/**
 * AllLegacyTests file
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @package       Cake.Test.Case
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy;

use Cake\Utility\Folder;
use PHPUnit\Framework\TestSuite;

/**
 * AllLegacyTests class
 *
 * This test group runs the tests of the application and the plugins in the CakePHP 2 layout,
 * without App.namespace. Run it apart from AllTests.
 *
 * @package       Cake.Test.Case
 */
class AllLegacyTestsTest extends TestSuite
{
    /**
     * Suite define the tests for this suite
     *
     * @return TestSuite
     */
    public static function suite(): mixed
    {
        $suite = new TestSuite('AllLegacyTests');

        $Folder = new Folder(__DIR__);
        $files = $Folder->tree(null, true, 'files');
        sort($files);
        foreach ($files as $file) {
            if (str_ends_with($file, 'Test.php') && $file !== __FILE__) {
                $suite->addTestFile($file);
            }
        }

        return $suite;
    }
}
