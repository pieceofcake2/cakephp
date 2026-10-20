<?php
/**
 * Loads an app fixture of the test application in the CakePHP 2 layout in a process of its own,
 * with TESTS set to the Test directory of that application, as in a 2.x application.
 * CakeFixtureManagerTest::testLoadLegacyAppFixture() runs it and reads the JSON it prints.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.TestSuite.Fixture
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeFixtureManager;
use Cake\Utility\ClassRegistry;

require dirname(__DIR__, 5) . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
define('TESTS', dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'test_legacy_app' . DIRECTORY_SEPARATOR . 'Test' . DIRECTORY_SEPARATOR);
require dirname(__DIR__, 4) . DIRECTORY_SEPARATOR . 'bootstrap.php';

$test = new class () extends CakeTestCase {
    public $fixtures = ['app.legacy_article'];
};
$manager = new CakeFixtureManager();
$manager->fixturize($test);
$manager->load($test);

$loaded = new ReflectionProperty($manager, '_loaded');
if (PHP_VERSION_ID < 80100) {
    $loaded->setAccessible(true);
}
$fixture = $loaded->getValue($manager)['app.legacy_article'];
$LegacyArticle = ClassRegistry::init(['class' => 'LegacyArticle', 'table' => 'legacy_articles']);
echo json_encode([
    'class' => get_class($fixture),
    'titles' => array_values($LegacyArticle->find('list', ['fields' => ['id', 'title'], 'order' => 'id'])),
]);

$manager->shutDown();
