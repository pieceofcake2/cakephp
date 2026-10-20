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
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Model\ConnectionManager;
use Cake\TestSuite\CakeTestCase;
use Cake\TestSuite\Fixture\CakeTestModel;
use Cake\Utility\ClassRegistry;
use TestApp\Model\NamespacedComment;
use TestApp\Model\NamespacedPost;
use TestPluginTwo\Model\TestPluginTwoAppModel;

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
    public $useTable = false;
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
    public $tablePrefix = 'something_';
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
    public $useDbConfig = 'doesnotexist';
}
class_alias(RegisterPrefixedDs::class, 'App\\Model\\RegisterPrefixedDs');

/**
 * Abstract class for testing ClassRegistry.
 */
abstract class ClassRegistryAbstractModel extends ClassRegisterModel
{
    abstract public function doSomething(): mixed;
}
class_alias(ClassRegistryAbstractModel::class, 'App\\Model\\ClassRegistryAbstractModel');

/**
 * Interface for testing ClassRegistry
 */
interface ClassRegistryInterfaceTest
{
    public function doSomething(): mixed;
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
    public function testAddModel(): void
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
    public function testAddModelWithAlias(): void
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
    public function testAddModelWithAliasAco(): void
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
    public function testClassRegistryFlush(): void
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
    public function testAddMultipleModels(): void
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
    public function testPluginAppModel(): void
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
    public function testPrefixedTestDatasource(): void
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
    public function testInitStrict(): void
    {
        $this->assertFalse(ClassRegistry::init('NonExistent', true));
    }

    /**
     * Test that you cannot init() an abstract class. An exception will be raised.
     *
     * @return void
     */
    public function testInitAbstractClass(): void
    {
        $this->expectException(CakeException::class);
        ClassRegistry::init('ClassRegistryAbstractModel');
    }

    /**
     * Test that you cannot init() an abstract class. A exception will be raised.
     *
     * @return void
     */
    public function testInitInterface(): void
    {
        $this->expectException(CakeException::class);
        ClassRegistry::init(ClassRegistryInterfaceTest::class);
    }

    /**
     * Test that getObject() returns false for a key that is neither registered nor mapped.
     *
     * @return void
     */
    public function testGetObjectUnknownKey(): void
    {
        $this->assertFalse(ClassRegistry::getObject('ThisKeyIsNotRegistered'));
    }

    /**
     * Test that models are found in App.namespace and named without the namespace.
     *
     * @return void
     */
    public function testInitNamespacedModel(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $result = ClassRegistry::init('NamespacedPost');
        $this->assertInstanceOf(NamespacedPost::class, $result);
        $this->assertSame('NamespacedPost', $result->name);
        $this->assertSame('NamespacedPost', $result->alias);
    }

    /**
     * Test that a model in App.namespace merges the vars of the AppModel of the namespace, and that the
     * AppModel of a plugin namespace is used for a plugin model without a class.
     *
     * @return void
     */
    public function testNamespacedAppModels(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $Comment = ClassRegistry::init('NamespacedComment');
        $this->assertInstanceOf(NamespacedComment::class, $Comment);
        $this->assertContains('Containable', $Comment->Behaviors->loaded());

        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]]);
        CakePlugin::load('TestPluginTwo');
        $Model = ClassRegistry::init(['class' => 'TestPluginTwo.NoSuchModel', 'table' => false]);
        $this->assertInstanceOf(TestPluginTwoAppModel::class, $Model);
        CakePlugin::unload();
    }

    /**
     * Test that the object stored for an alias is returned for a model in App.namespace.
     *
     * @return void
     */
    public function testDuplicateNamespacedModel(): void
    {
        Configure::write('App.namespace', 'TestApp');
        $post = ClassRegistry::init(['class' => 'NamespacedPost', 'alias' => 'Article']);
        $this->assertInstanceOf(NamespacedPost::class, $post);
        $this->assertSame($post, ClassRegistry::init(['class' => 'NamespacedPost', 'alias' => 'Article']));
    }

    /**
     * Test that an object stored with the short name of its class is found by its fully qualified class name.
     *
     * @return void
     */
    public function testFullyQualifiedClassNameAsKey(): void
    {
        $post = ClassRegistry::init(['class' => NamespacedPost::class, 'alias' => 'NamespacedPost']);
        $this->assertSame($post, ClassRegistry::getObject('NamespacedPost'));
        $this->assertSame($post, ClassRegistry::getObject(NamespacedPost::class));
        $this->assertSame($post, ClassRegistry::getObject('\\' . NamespacedPost::class));
        $this->assertTrue(ClassRegistry::isKeySet(NamespacedPost::class));

        ClassRegistry::removeObject(NamespacedPost::class);
        $this->assertFalse(ClassRegistry::getObject('NamespacedPost'));

        // Not an object of another class with the same short name
        ClassRegistry::addObject('NamespacedPost', new NamespacedComment());
        $this->assertFalse(ClassRegistry::getObject(NamespacedPost::class));
    }
}
