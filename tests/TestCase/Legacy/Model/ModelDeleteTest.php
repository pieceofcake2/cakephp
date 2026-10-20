<?php
/**
 * ModelDeleteTest file
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
 * @package       Cake.Test.Case.Model
 * @since         CakePHP(tm) v 1.2.0.4206
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Legacy\Model;

use Article;
use Cake\Core\App;
use Cake\Core\CakePlugin;

App::uses('Article', 'Model');

/**
 * ModelDeleteTest
 *
 * @package       Cake.Test.Case.Model
 */
class ModelDeleteTest extends BaseModelTest
{
    /**
     * test that a plugin model as the 'with' model doesn't have issues
     *
     * @return void
     */
    public function testDeleteLinksWithPLuginJoinModel(): void
    {
        App::build([
            'Plugin' => [CORE_TESTS . DS . 'test_legacy_app' . DS . 'Plugin' . DS],
        ]);
        CakePlugin::load('TestLegacyPlugin');
        $this->loadFixtures('Article', 'ArticlesTag', 'Tag', 'Comment', 'Attachment');
        $Article = new Article();
        $Article->unbindModel(['hasAndBelongsToMany' => ['Tag']], false);
        unset($Article->Tag, $Article->ArticleTags);
        $Article->bindModel(['hasAndBelongsToMany' => [
            'Tag' => ['with' => 'TestLegacyPlugin.ArticlesTag'],
        ]], false);

        $Article->ArticlesTag->order = null;
        $this->assertTrue($Article->delete(1));
        CakePlugin::unload('TestLegacyPlugin');
    }
}
