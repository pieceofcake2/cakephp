<?php

declare(strict_types=1);

/**
 * Rector Configuration for CakePHP 2.x
 *
 * This configuration applies custom refactoring rules for CakePHP classes.
 *
 * Usage:
 *   ./vendor/bin/rector process src tests
 *   ./vendor/bin/rector process src tests --dry-run (preview changes)
 *
 * Custom Rules:
 *   - CakeTestFixturePropertyTypeRector - Adds property types to CakeTestFixture subclasses
 *   - AddPropertyTypeDeclarationRector - Adds property types to Controller, Component, Helper subclasses
 */

use Cake\Rector\TypeDeclaration\CakeTestFixturePropertyTypeRector;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\StringType;
use PHPStan\Type\UnionType;
use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\ClassMethod\AddReturnTypeDeclarationRector;
use Rector\TypeDeclaration\Rector\Property\AddPropertyTypeDeclarationRector;
use Rector\TypeDeclaration\ValueObject\AddPropertyTypeDeclaration;
use Rector\TypeDeclaration\ValueObject\AddReturnTypeDeclaration;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/src',
        __DIR__ . '/tests',
    ])
    ->withRules([
        CakeTestFixturePropertyTypeRector::class,
    ])
    ->withConfiguredRule(AddPropertyTypeDeclarationRector::class, [
        // Controller properties
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'name', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'controllerClass', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'uses', new UnionType([new ArrayType(new MixedType(), new MixedType()), new BooleanType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'helpers', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'components', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'request', new UnionType([new ObjectType('Cake\Network\CakeRequest'), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'response', new UnionType([new ObjectType('Cake\Network\CakeResponse'), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'viewPath', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'layoutPath', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'viewVars', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'view', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'autoRender', new BooleanType()),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'autoLayout', new BooleanType()),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'Components', new ObjectType('Cake\Controller\ComponentCollection')),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'viewClass', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'View', new ObjectType('Cake\View\View')),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'ext', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'plugin', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'methods', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'modelClass', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'modelKey', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', 'validationErrors', new UnionType([new ArrayType(new MixedType(), new MixedType()), new BooleanType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', '_mergeParent', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', '_eventManager', new UnionType([new ObjectType('Cake\Event\CakeEventManager'), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Controller\Controller', '_responseClass', new StringType()),

        // Component properties
        new AddPropertyTypeDeclaration('Cake\Controller\Component', 'settings', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Controller\Component', 'components', new ArrayType(new MixedType(), new MixedType())),

        // Helper properties
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', 'helpers', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', 'settings', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', 'theme', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', 'plugin', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', 'fieldset', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', 'tags', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', '_View', new ObjectType('Cake\View\View')),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', '_fieldSuffixes', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', '_minimizedAttributes', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', '_attributeFormat', new StringType()),
        new AddPropertyTypeDeclaration('Cake\View\Helper\Helper', '_minimizedAttributeFormat', new StringType()),

        // View properties
        new AddPropertyTypeDeclaration('Cake\View\View', 'Helpers', new ObjectType('Cake\View\HelperCollection')),
        new AddPropertyTypeDeclaration('Cake\View\View', 'Blocks', new ObjectType('Cake\View\ViewBlock')),
        new AddPropertyTypeDeclaration('Cake\View\View', 'name', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'controllerClass', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'plugin', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'helpers', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\View', 'viewPath', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'view', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'layout', new UnionType([new StringType(), new BooleanType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'layoutPath', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'autoLayout', new BooleanType()),
        new AddPropertyTypeDeclaration('Cake\View\View', 'ext', new StringType()),
        new AddPropertyTypeDeclaration('Cake\View\View', 'subDir', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'theme', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'hasRendered', new BooleanType()),
        new AddPropertyTypeDeclaration('Cake\View\View', 'viewVars', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\View', 'passedArgs', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\View', 'validationErrors', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\View', 'uuids', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\View\View', 'request', new UnionType([new ObjectType('Cake\Network\CakeRequest'), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'response', new UnionType([new ObjectType('Cake\Network\CakeResponse'), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\View\View', 'elementCache', new StringType()),
        new AddPropertyTypeDeclaration('Cake\View\View', 'elementCacheSettings', new ArrayType(new MixedType(), new MixedType())),

        // CacheEngine properties
        new AddPropertyTypeDeclaration('Cake\Cache\CacheEngine', 'settings', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Cache\CacheEngine', '_groupPrefix', new UnionType([new StringType(), new NullType()])),

        // Model properties
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'useDbConfig', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'useTable', new UnionType([new StringType(), new BooleanType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'displayField', new UnionType([new StringType(), new BooleanType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'schemaName', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'table', new UnionType([new StringType(), new BooleanType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'primaryKey', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'validate', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'validationErrors', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'validationDomain', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'tablePrefix', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'plugin', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'name', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'alias', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'tableToModel', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'cacheQueries', new BooleanType()),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'belongsTo', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'hasOne', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'hasMany', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'hasAndBelongsToMany', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'actsAs', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\Model', 'order', new UnionType([new ArrayType(new MixedType(), new MixedType()), new StringType(), new NullType()])),

        // CakeRequest properties
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'params', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'data', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'query', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'url', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'base', new UnionType([new StringType(), new BooleanType()])),
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'webroot', new StringType()),
        new AddPropertyTypeDeclaration('Cake\Network\CakeRequest', 'here', new UnionType([new StringType(), new NullType()])),

        // ModelBehavior properties
        new AddPropertyTypeDeclaration('Cake\Model\ModelBehavior', 'settings', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\ModelBehavior', 'mapMethods', new ArrayType(new MixedType(), new MixedType())),

        // BehaviorCollection properties
        new AddPropertyTypeDeclaration('Cake\Model\BehaviorCollection', 'modelName', new UnionType([new StringType(), new NullType()])),
        new AddPropertyTypeDeclaration('Cake\Model\BehaviorCollection', '_methods', new ArrayType(new MixedType(), new MixedType())),
        new AddPropertyTypeDeclaration('Cake\Model\BehaviorCollection', '_mappedMethods', new ArrayType(new MixedType(), new MixedType())),
    ])
    ->withConfiguredRule(AddReturnTypeDeclarationRector::class, [
        // CakeEventListener::implementedEvents()
        new AddReturnTypeDeclaration('Cake\Event\CakeEventListener', 'implementedEvents', new ArrayType(new MixedType(), new MixedType())),
        new AddReturnTypeDeclaration('Cake\Controller\Controller', 'implementedEvents', new ArrayType(new MixedType(), new MixedType())),
        new AddReturnTypeDeclaration('Cake\Model\Model', 'implementedEvents', new ArrayType(new MixedType(), new MixedType())),
        new AddReturnTypeDeclaration('Cake\Controller\Component', 'implementedEvents', new ArrayType(new MixedType(), new MixedType())),
    ])
    ->withSkip([
    ]);
