<?php
/**
 * ClassRegistryTest file
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
 * @since         CakePHP(tm) v 1.2.0.5432
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Utility;

use AppModel;
use Cake\Core\CakePlugin;
use Cake\Error\CakeException;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeTestModel;
use Cake\Utility\ClassRegistry;

/**
 * ClassRegisterModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class ClassRegisterModel extends CakeTestModel
{
    /**
     * useTable property
     *
     * @var string|bool|null
     */
    public string|bool|null $useTable = false;
}
class_alias(ClassRegisterModel::class, 'App\\Model\\ClassRegisterModel');

/**
 * RegisterArticle class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticle extends ClassRegisterModel
{
}
class_alias(RegisterArticle::class, 'App\\Model\\RegisterArticle');

/**
 * RegisterArticleFeatured class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticleFeatured extends ClassRegisterModel
{
}
class_alias(RegisterArticleFeatured::class, 'App\\Model\\RegisterArticleFeatured');

/**
 * RegisterArticleTag class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterArticleTag extends ClassRegisterModel
{
}
class_alias(RegisterArticleTag::class, 'App\\Model\\RegisterArticleTag');

/**
 * RegistryPluginAppModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegistryPluginAppModel extends ClassRegisterModel
{
    /**
     * tablePrefix property
     *
     * @var string|null
     */
    public ?string $tablePrefix = 'something_';
}
class_alias(RegistryPluginAppModel::class, 'App\\Model\\RegistryPluginAppModel');
class_alias(RegistryPluginAppModel::class, 'RegistryPlugin\\Model\\RegistryPluginAppModel');

/**
 * TestRegistryPluginModel class
 *
 * @package       Cake.Test.Case.Utility
 */
class TestRegistryPluginModel extends RegistryPluginAppModel
{
}
class_alias(TestRegistryPluginModel::class, 'App\\Model\\TestRegistryPluginModel');
class_alias(TestRegistryPluginModel::class, 'RegistryPlugin\\Model\\TestRegistryPluginModel');

/**
 * RegisterCategory class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterCategory extends ClassRegisterModel
{
}
class_alias(RegisterCategory::class, 'App\\Model\\RegisterCategory');

/**
 * RegisterPrefixedDs class
 *
 * @package       Cake.Test.Case.Utility
 */
class RegisterPrefixedDs extends ClassRegisterModel
{
    /**
     * useDbConfig property
     *
     * @var string
     */
    public string $useDbConfig = 'doesnotexist';
}
class_alias(RegisterPrefixedDs::class, 'App\\Model\\RegisterPrefixedDs');

/**
 * Abstract class for testing ClassRegistry.
 */
abstract class ClassRegistryAbstractModel extends ClassRegisterModel
{
    abstract public function doSomething();
}
class_alias(ClassRegistryAbstractModel::class, 'App\\Model\\ClassRegistryAbstractModel');

/**
 * Interface for testing ClassRegistry
 */
interface ClassRegistryInterfaceTest
{
    public function doSomething();
}

/**
 * ClassRegistryTest class
 *
 * @package       Cake.Test.Case.Utility
 */
class ClassRegistryTest extends CakeTestCase
{
    /**
     * testAddModel method
     *
     * @return void
     */
    public function testAddModel()
    {
        $tag = ClassRegistry::init('RegisterArticleTag');
        $this->assertInstanceOf(RegisterArticleTag::class, $tag);

        $tagCopy = ClassRegistry::isKeySet('RegisterArticleTag');
        $this->assertTrue($tagCopy);

        $tag->name = 'SomeNewName';

        $tagCopy = ClassRegistry::getObject('RegisterArticleTag');

        $this->assertInstanceOf(RegisterArticleTag::class, $tagCopy);
        $this->assertSame($tag, $tagCopy);

        $newTag = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'NewTag']);
        $this->assertInstanceOf(RegisterArticleTag::class, $newTag);

        $newTagCopy = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'NewTag']);

        $this->assertNotSame($tag, $newTag);
        $this->assertSame($newTag, $newTagCopy);

        $newTag->name = 'SomeOtherName';
        $this->assertNotSame($tag, $newTag);
        $this->assertSame($newTag, $newTagCopy);

        $tag->name = 'SomeOtherName';
        $this->assertNotSame($tag, $newTag);

        $this->assertTrue($tagCopy->name === 'SomeOtherName');

        $user = ClassRegistry::init(['class' => 'RegisterUser', 'alias' => 'User', 'table' => false]);
        $this->assertInstanceOf(AppModel::class, $user);

        $userCopy = ClassRegistry::init(['class' => 'RegisterUser', 'alias' => 'User', 'table' => false]);
        $this->assertInstanceOf(AppModel::class, $userCopy);
        $this->assertEquals($user, $userCopy);

        $category = ClassRegistry::init(['class' => 'RegisterCategory']);
        $this->assertInstanceOf(RegisterCategory::class, $category);

        $parentCategory = ClassRegistry::init(['class' => 'RegisterCategory', 'alias' => 'ParentCategory']);
        $this->assertInstanceOf(RegisterCategory::class, $parentCategory);
        $this->assertNotSame($category, $parentCategory);

        $this->assertNotEquals($category->alias, $parentCategory->alias);
        $this->assertEquals('RegisterCategory', $category->alias);
        $this->assertEquals('ParentCategory', $parentCategory->alias);
    }

    /**
     * Test that init() can make models with alias set properly
     *
     * @return void
     */
    public function testAddModelWithAlias()
    {
        $tag = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'NewTag']);
        $this->assertInstanceOf(RegisterArticleTag::class, $tag);
        $this->assertSame('NewTag', $tag->alias);
        $this->assertSame('RegisterArticleTag', $tag->name);

        $newTag = ClassRegistry::init(['class' => 'RegisterArticleTag', 'alias' => 'OtherTag']);
        $this->assertInstanceOf(RegisterArticleTag::class, $tag);
        $this->assertSame('OtherTag', $newTag->alias);
        $this->assertSame('RegisterArticleTag', $newTag->name);
    }

    /**
     * Test that init() can make the Aco models with alias set properly
     *
     * @return void
     */
    public function testAddModelWithAliasAco()
    {
        $aco = ClassRegistry::init(['class' => 'Aco', 'alias' => 'CustomAco']);
        $this->assertInstanceOf('Aco', $aco);
        $this->assertSame('Aco', $aco->name);
        $this->assertSame('CustomAco', $aco->alias);
    }

    /**
     * testClassRegistryFlush method
     *
     * @return void
     */
    public function testClassRegistryFlush()
    {
        ClassRegistry::init('RegisterArticleTag');

        $ArticleTag = ClassRegistry::getObject('RegisterArticleTag');
        $this->assertInstanceOf(RegisterArticleTag::class, $ArticleTag);
        ClassRegistry::flush();

        $NoArticleTag = ClassRegistry::isKeySet('RegisterArticleTag');
        $this->assertFalse($NoArticleTag);
        $this->assertInstanceOf(RegisterArticleTag::class, $ArticleTag);
    }

    /**
     * testAddMultipleModels method
     *
     * @return void
     */
    public function testAddMultipleModels()
    {
        $Article = ClassRegistry::isKeySet('Article');
        $this->assertFalse($Article);

        $Featured = ClassRegistry::isKeySet('Featured');
        $this->assertFalse($Featured);

        $Tag = ClassRegistry::isKeySet('Tag');
        $this->assertFalse($Tag);

        $models = [
            ['class' => 'RegisterArticle', 'alias' => 'Article'],
            ['class' => 'RegisterArticleFeatured', 'alias' => 'Featured'],
            ['class' => 'RegisterArticleTag', 'alias' => 'Tag'],
        ];

        $added = ClassRegistry::init($models);
        $this->assertTrue($added);

        $Article = ClassRegistry::isKeySet('Article');
        $this->assertTrue($Article);

        $Featured = ClassRegistry::isKeySet('Featured');
        $this->assertTrue($Featured);

        $Tag = ClassRegistry::isKeySet('Tag');
        $this->assertTrue($Tag);

        $Article = ClassRegistry::getObject('Article');
        $this->assertInstanceOf(RegisterArticle::class, $Article);

        $Featured = ClassRegistry::getObject('Featured');
        $this->assertInstanceOf(RegisterArticleFeatured::class, $Featured);

        $Tag = ClassRegistry::getObject('Tag');
        $this->assertInstanceOf(RegisterArticleTag::class, $Tag);
    }

    /**
     * testPluginAppModel method
     *
     * @return void
     */
    public function testPluginAppModel()
    {
        $testRegistryPluginModel = ClassRegistry::isKeySet('TestRegistryPluginModel');
        $this->assertFalse($testRegistryPluginModel);

        //Faking a plugin
        CakePlugin::load('RegistryPlugin', ['path' => '/fake/path']);
        $testRegistryPluginModel = ClassRegistry::init('RegistryPlugin.TestRegistryPluginModel');
        $this->assertInstanceOf(TestRegistryPluginModel::class, $testRegistryPluginModel);

        $this->assertEquals('something_', $testRegistryPluginModel->tablePrefix);

        $PluginUser = ClassRegistry::init(['class' => 'RegistryPlugin.RegisterUser', 'alias' => 'RegistryPluginUser', 'table' => false]);
        $this->assertInstanceOf(RegistryPluginAppModel::class, $PluginUser);

        $PluginUserCopy = ClassRegistry::getObject('RegistryPluginUser');
        $this->assertInstanceOf(RegistryPluginAppModel::class, $PluginUserCopy);
        $this->assertSame($PluginUser, $PluginUserCopy);
        CakePlugin::unload();
    }

    /**
     * Tests prefixed datasource names for test purposes
     *
     * @return void
     */
    public function testPrefixedTestDatasource()
    {
        ClassRegistry::config(['testing' => true]);
        $Model = ClassRegistry::init('RegisterPrefixedDs');
        $this->assertEquals('test', $Model->useDbConfig);
        ClassRegistry::removeObject('RegisterPrefixedDs');

        $testConfig = ConnectionManager::getDataSource('test')->config;
        ConnectionManager::create('test_doesnotexist', $testConfig);

        $Model = ClassRegistry::init('RegisterArticle');
        $this->assertEquals('test', $Model->useDbConfig);
        $Model = ClassRegistry::init('RegisterPrefixedDs');
        $this->assertEquals('test_doesnotexist', $Model->useDbConfig);
    }

    /**
     * Tests that passing the string parameter to init() will return false if the model does not exists
     *
     * @return void
     */
    public function testInitStrict()
    {
        $this->assertFalse(ClassRegistry::init('NonExistent', true));
    }

    /**
     * Test that you cannot init() an abstract class. An exception will be raised.
     *
     * @return void
     */
    public function testInitAbstractClass()
    {
        $this->expectException(CakeException::class);
        ClassRegistry::init('ClassRegistryAbstractModel');
    }

    /**
     * Test that you cannot init() an abstract class. A exception will be raised.
     *
     * @return void
     */
    public function testInitInterface()
    {
        $this->expectException(CakeException::class);
        ClassRegistry::init(ClassRegistryInterfaceTest::class);
    }
}
