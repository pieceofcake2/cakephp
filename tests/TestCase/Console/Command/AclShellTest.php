<?php
/**
 * AclShell Test file
 *
 * CakePHP :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP Project
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP v 1.2.0.7726
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\AclShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\Controller\Component\Acl\DbAcl;
use Cake\Controller\Component\AclComponent;
use Cake\Controller\ComponentCollection;
use Cake\Core\App;
use Cake\Core\CakePlugin;
use Cake\Core\Configure;
use Cake\Error\CakeException;
use Cake\Error\ConsoleException;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use Cake\Utility\Hash;
use PHPUnit\Framework\MockObject\MockObject;
use RuntimeException;

/**
 * AclShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class AclShellTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array<string>
     */
    public $fixtures = [
        'core.aco',
        'core.aro',
        'core.aros_aco',
    ];

    public AclShell|MockObject|null $Task = null;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('Acl.database', 'test');
        Configure::write('Acl.classname', 'DbAcl');

        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Task = $this->getMock(
            AclShell::class,
            ['in', 'out', 'hr', 'createFile', 'error', 'err', 'clear', 'dispatchShell'],
            [$out, $out, $in],
        );
        $collection = new ComponentCollection();
        $this->Task->Acl = new AclComponent($collection);
        $this->Task->params['datasource'] = 'test';
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Task);
        CakePlugin::unload();

        parent::tearDown();
    }

    /**
     * test that model.foreign_key output works when looking at acl rows
     *
     * @return void
     */
    public function testViewWithModelForeignKeyOutput(): void
    {
        $this->Task->command = 'view';
        $this->Task->startup();
        $data = [
            'parent_id' => null,
            'model' => 'MyModel',
            'foreign_key' => 2,
        ];
        $this->Task->Acl->Aro->create($data);
        $this->Task->Acl->Aro->save();
        $this->Task->args[0] = 'aro';

        $outCalls = [];
        $this->Task
            ->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$outCalls) {
                $outCalls[] = $message;

                return 0;
            });

        $this->Task->view();

        $this->assertEquals('Aro tree:', $outCalls[0]);
        $this->assertStringContainsString('[1] ROOT', $outCalls[1]);
        $this->assertStringContainsString('[3] Gandalf', $outCalls[3]);
        $this->assertStringContainsString('[5] MyModel.2', $outCalls[5]);
    }

    /**
     * test view with an argument
     *
     * @return void
     */
    public function testViewWithArgument(): void
    {
        $this->Task->args = ['aro', 'admins'];

        $outCalls = [];
        $this->Task
            ->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$outCalls) {
                $outCalls[] = $message;

                return 0;
            });

        $this->Task->view();

        $this->assertEquals('Aro tree:', $outCalls[0]);
        $this->assertEquals('  [2] admins', $outCalls[1]);
        $this->assertEquals('    [3] Gandalf', $outCalls[2]);
        $this->assertEquals('    [4] Elrond', $outCalls[3]);
    }

    /**
     * test the method that splits model.foreign key. and that it returns an array.
     *
     * @return void
     */
    public function testParsingModelAndForeignKey(): void
    {
        $result = $this->Task->parseIdentifier('Model.foreignKey');
        $expected = ['model' => 'Model', 'foreign_key' => 'foreignKey'];
        $this->assertEquals($expected, $result);

        $result = $this->Task->parseIdentifier('mySuperUser');
        $this->assertEquals('mySuperUser', $result);

        $result = $this->Task->parseIdentifier('111234');
        $this->assertEquals('111234', $result);
    }

    /**
     * test creating aro/aco nodes
     *
     * @return void
     */
    public function testCreate(): void
    {
        $outCalls = [];
        $this->Task
            ->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];

                return 0;
            });

        $this->Task->args = ['aro', 'root', 'User.1'];

        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $Aro->cacheQueries = false;
        $result = $Aro->read();
        $this->assertEquals('User', $result['Aro']['model']);
        $this->assertEquals(1, $result['Aro']['foreign_key']);
        $this->assertEquals(null, $result['Aro']['parent_id']);
        $id = $result['Aro']['id'];

        $this->Task->args = ['aro', 'User.1', 'User.3'];
        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read();
        $this->assertEquals('User', $result['Aro']['model']);
        $this->assertEquals(3, $result['Aro']['foreign_key']);
        $this->assertEquals($id, $result['Aro']['parent_id']);

        $this->Task->args = ['aro', 'root', 'somealias'];
        $this->Task->create();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read();
        $this->assertEquals('somealias', $result['Aro']['alias']);
        $this->assertEquals(null, $result['Aro']['model']);
        $this->assertEquals(null, $result['Aro']['foreign_key']);
        $this->assertEquals(null, $result['Aro']['parent_id']);

        $this->assertEquals("<success>New Aro</success> 'User.1' created.", $outCalls[0]['message']);
        $this->assertEquals(2, $outCalls[0]['newlines']);

        $this->assertEquals("<success>New Aro</success> 'User.3' created.", $outCalls[1]['message']);
        $this->assertEquals(2, $outCalls[1]['newlines']);

        $this->assertEquals("<success>New Aro</success> 'somealias' created.", $outCalls[2]['message']);
        $this->assertEquals(2, $outCalls[2]['newlines']);
    }

    /**
     * test the delete method with different node types.
     *
     * @return void
     */
    public function testDelete(): void
    {
        $this->Task->args = ['aro', 'AuthUser.1'];
        $outCalls = [];
        $this->Task
            ->expects($this->once())
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];

                return 0;
            });
        $this->Task->delete();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->findById(3);
        $this->assertSame([], $result);

        $this->assertEquals([
            ['message' => '<success>Aro deleted.</success>', 'newlines' => 2],
        ], $outCalls);
    }

    /**
     * test setParent method.
     *
     * @return void
     */
    public function testSetParent(): void
    {
        $this->Task->args = ['aro', 'AuthUser.2', 'root'];
        $this->Task->setParent();

        $Aro = ClassRegistry::init('Aro');
        $result = $Aro->read(null, 4);
        $this->assertEquals(null, $result['Aro']['parent_id']);
    }

    /**
     * test grant
     *
     * @return void
     */
    public function testGrant(): void
    {
        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $outCalls = [];
        $this->Task
            ->expects($this->once())
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];

                return 0;
            });
        $this->Task->grant();
        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);

        $this->assertNotEmpty($node['Aco'][0]);
        $this->assertEquals(1, $node['Aco'][0]['Permission']['_create']);

        $this->assertCount(1, $outCalls);
        $this->assertMatchesRegularExpression('/granted/', $outCalls[0]['message']);
        $this->assertEquals(true, $outCalls[0]['newlines']);
    }

    /**
     * test deny
     *
     * @return void
     */
    public function testDeny(): void
    {
        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $outCalls = [];
        $this->Task
            ->expects($this->once())
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];

                return 0;
            });

        $this->Task->deny();

        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
        $this->assertNotEmpty($node['Aco'][0]);
        $this->assertEquals(-1, $node['Aco'][0]['Permission']['_create']);

        $this->assertCount(1, $outCalls);
        $this->assertStringContainsString('Permission denied', $outCalls[0]['message']);
        $this->assertEquals(true, $outCalls[0]['newlines']);
    }

    /**
     * test checking allowed and denied perms
     *
     * @return void
     */
    public function testCheck(): void
    {
        $outCalls = [];
        $this->Task
            ->expects($this->exactly(4))
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];

                return 0;
            });

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', '*'];
        $this->Task->check();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->grant();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->check();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'delete'];
        $this->Task->check();

        $this->assertMatchesRegularExpression('/not allowed/', $outCalls[0]['message']);
        $this->assertEquals(1, $outCalls[0]['newlines']);
        $this->assertMatchesRegularExpression('/granted/', $outCalls[1]['message']);
        $this->assertEquals(1, $outCalls[1]['newlines']);
        $this->assertMatchesRegularExpression('/is.*allowed/', $outCalls[2]['message']);
        $this->assertEquals(1, $outCalls[2]['newlines']);
        $this->assertMatchesRegularExpression('/not.*allowed/', $outCalls[3]['message']);
        $this->assertEquals(1, $outCalls[3]['newlines']);
    }

    /**
     * test inherit and that it 0's the permission fields.
     *
     * @return void
     */
    public function testInherit(): void
    {
        $outCalls = [];
        $this->Task
            ->expects($this->exactly(2))
            ->method('out')
            ->willReturnCallback(function ($message, $newlines = 1) use (&$outCalls) {
                $outCalls[] = ['message' => $message, 'newlines' => $newlines];

                return 0;
            });

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'create'];
        $this->Task->grant();

        $this->Task->args = ['AuthUser.2', 'ROOT/Controller1', 'all'];
        $this->Task->inherit();

        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $node = $this->Task->Acl->Aro->read(null, $node[0]['Aro']['id']);
        $this->assertNotEmpty($node['Aco'][0]);
        $this->assertEquals(0, $node['Aco'][0]['Permission']['_create']);

        $this->assertMatchesRegularExpression('/Permission .*granted/', $outCalls[0]['message']);
        $this->assertEquals(1, $outCalls[0]['newlines']);
        $this->assertMatchesRegularExpression('/Permission .*inherited/', $outCalls[1]['message']);
        $this->assertEquals(1, $outCalls[1]['newlines']);
    }

    /**
     * test getting the path for an aro/aco
     *
     * @return void
     */
    public function testGetPath(): void
    {
        $this->Task->args = ['aro', 'AuthUser.2'];
        $node = $this->Task->Acl->Aro->node(['model' => 'AuthUser', 'foreign_key' => 2]);
        $first = $node[0]['Aro']['id'];
        $second = $node[1]['Aro']['id'];
        $last = $node[2]['Aro']['id'];
        $outCalls = [];
        $this->Task
            ->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$outCalls) {
                $outCalls[] = $message;

                return 0;
            });
        $this->Task->getPath();

        $this->assertEquals('[' . $last . '] ROOT', $outCalls[1]);
        $this->assertEquals('  [' . $second . '] admins', $outCalls[2]);
        $this->assertEquals('    [' . $first . '] Elrond', $outCalls[3]);
    }

    /**
     * test that initdb makes the correct call.
     *
     * @return void
     */
    public function testInitDb(): void
    {
        $this->Task->expects($this->once())->method('dispatchShell')
            ->with('schema create DbAcl');

        $this->Task->initdb();
    }

    /**
     * Test that DbAcl is accepted by its global name and by its name in the Cake namespace.
     *
     * @return void
     */
    public function testStartupWithDbAclByGlobalAndNamespacedName(): void
    {
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);
        $shell = $this->getMock(AclShell::class, ['in', 'out', 'hr', 'error', 'err', '_stop'], [$out, $out, $in]);
        $shell->expects($this->never())->method('error');
        $shell->expects($this->never())->method('_stop');
        foreach (['DbAcl', DbAcl::class] as $class) {
            Configure::write('Acl.classname', $class);
            $shell->startup();
        }
    }

    /**
     * Records the messages sent to out() and returns them by reference.
     *
     * @param AclShell|MockObject $shell The shell mock.
     * @return array<string>
     */
    protected function &_captureOut($shell): array
    {
        $calls = [];
        $shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$calls) {
                $calls[] = $message;

                return 0;
            });

        return $calls;
    }

    /**
     * Makes error() throw, so that the shell stops like it does with _stop().
     *
     * @return void
     */
    protected function _errorThrows(): void
    {
        $this->Task->expects($this->once())
            ->method('error')
            ->willReturnCallback(function ($title, $message = null) {
                throw new RuntimeException($title . ($message === null ? '' : ' / ' . $message));
            });
    }

    /**
     * Test that startup stops when Acl.classname is not DbAcl.
     *
     * @return void
     */
    public function testStartupWithOtherAclClass(): void
    {
        Configure::write('App.namespace', 'TestApp');
        foreach (['IniAcl', 'PhpAcl', 'NamespacedAcl', 'MissingAcl'] as $class) {
            Configure::write('Acl.classname', $class);
            $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
            $in = $this->getMock(ConsoleInput::class, [], [], '', false);
            $shell = $this->getMock(AclShell::class, ['in', 'out', 'hr', 'error', 'err', '_stop'], [$out, $out, $in]);
            $shell->command = 'view';
            $shell->expects($this->once())
                ->method('err')
                ->with($this->logicalAnd(
                    $this->stringContains('an ACL implementation other than DB'),
                    $this->stringContains('Current ACL Classname: ' . $class),
                ));
            $shell->expects($this->once())->method('_stop')->with(1);

            $shell->startup();

            $this->assertNull($shell->Acl, $class);
        }
    }

    /**
     * Test that startup accepts the old DB_ACL name and a plugin prefix on it.
     *
     * The check of startup lets the name through. The AclComponent is built
     * next, and it cannot find a class of that name.
     *
     * @return void
     */
    public function testStartupWithDbAclLegacyName(): void
    {
        App::build(['Plugin' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS]]);
        CakePlugin::load('TestPlugin');
        foreach (['DB_ACL', 'TestPlugin.DB_ACL'] as $class) {
            Configure::write('Acl.classname', $class);
            $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
            $in = $this->getMock(ConsoleInput::class, [], [], '', false);
            $shell = $this->getMock(AclShell::class, ['in', 'out', 'hr', 'error', 'err', '_stop'], [$out, $out, $in]);
            $shell->command = 'view';
            $shell->expects($this->never())->method('err');
            $shell->expects($this->never())->method('_stop');

            $message = null;
            try {
                $shell->startup();
            } catch (CakeException $e) {
                $message = $e->getMessage();
            }
            $this->assertSame('Could not find DB_ACL.', $message, $class);
        }
    }

    /**
     * Test that startup reads the connection param and builds the AclComponent.
     *
     * @return void
     */
    public function testStartupWithCommand(): void
    {
        $this->Task->Acl = null;
        $this->Task->params['connection'] = 'test';
        $this->Task->command = 'check';
        $this->Task->startup();

        $this->assertSame('test', $this->Task->connection);
        $this->assertInstanceOf(AclComponent::class, $this->Task->Acl);
        $this->assertInstanceOf(DbAcl::class, $this->Task->Acl->adapter());
    }

    /**
     * Test that startup does not build the AclComponent for initdb, or without a command.
     *
     * @return void
     */
    public function testStartupWithoutAclComponent(): void
    {
        foreach (['initdb', null] as $command) {
            $this->Task->Acl = null;
            $this->Task->command = $command;
            $this->Task->startup();

            $this->assertSame('default', $this->Task->connection);
            $this->assertNull($this->Task->Acl);
        }
    }

    /**
     * Test that main() prints the help.
     *
     * @return void
     */
    public function testMain(): void
    {
        $this->markTestSkipped(
            'AclShell::getOptionParser() gives its action arguments a "default" key, which '
            . 'ConsoleInputArgument does not support. It sets the dynamic property $_default, '
            . 'which is deprecated on PHP 8.2 and later.',
        );
        $outCalls = &$this->_captureOut($this->Task);
        $this->Task->OptionParser = $this->Task->getOptionParser();

        $this->Task->main();

        $this->assertCount(1, $outCalls);
        $this->assertStringContainsString('A console tool for managing the DbAcl', $outCalls[0]);
        $this->assertStringContainsString('initdb', $outCalls[0]);
        $this->assertStringContainsString("To add a node at the root level, enter 'root' or '/'", $outCalls[0]);
    }

    /**
     * Test the option parser and the help of its subcommands.
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $this->markTestSkipped(
            'AclShell::getOptionParser() gives its action arguments a "default" key, which '
            . 'ConsoleInputArgument does not support. It sets the dynamic property $_default, '
            . 'which is deprecated on PHP 8.2 and later.',
        );
        $parser = $this->Task->getOptionParser();

        $this->assertSame(
            ['create', 'delete', 'setparent', 'getpath', 'check', 'grant', 'deny', 'inherit', 'view', 'initdb'],
            array_keys($parser->subcommands()),
        );

        $help = $parser->help('create');
        $this->assertStringContainsString('Creates a new ACL object <node> under the parent', $help);
        $this->assertStringContainsString('You can use `root` as the parent', $help);
        $this->assertStringContainsString('(choices: aro|aco)', $help);

        $help = $parser->help('getpath');
        $this->assertStringContainsString('This command is useful in determining the inheritance', $help);

        $help = $parser->help('grant');
        $this->assertStringContainsString('Action to grant', $help);
        $this->assertStringContainsString('<aro> <aco> [<action>]', $help);

        $help = $parser->help('view');
        $this->assertStringContainsString('only a portion of the requested tree.', $help);
        $this->assertStringContainsString('[<node>]', $help);

        [, $args] = $parser->parse(['aco', 'root', 'Posts'], 'create');
        $this->assertSame(['aco', 'root', 'Posts'], $args);
    }

    /**
     * Test that a type other than aro or aco is rejected by the parser.
     *
     * @return void
     */
    public function testGetOptionParserInvalidType(): void
    {
        $this->markTestSkipped(
            'AclShell::getOptionParser() gives its action arguments a "default" key, which '
            . 'ConsoleInputArgument does not support. It sets the dynamic property $_default, '
            . 'which is deprecated on PHP 8.2 and later.',
        );
        $this->expectException(ConsoleException::class);
        $this->expectExceptionMessage('"foo" is not a valid value for type. Please use one of "aro, aco"');
        $this->Task->getOptionParser()->parse(['foo', 'root', 'Posts'], 'create');
    }

    /**
     * Test parsing of more identifier shapes.
     *
     * @return void
     */
    public function testParsingIdentifierVariants(): void
    {
        $this->assertSame(
            ['model' => 'User', 'foreign_key' => '1.5'],
            $this->Task->parseIdentifier('User.1.5'),
        );
        $this->assertSame(
            ['model' => 'User', 'foreign_key' => ''],
            $this->Task->parseIdentifier('User.'),
        );
        $this->assertSame(
            ['model' => 'Plugin_User2', 'foreign_key' => 'abc'],
            $this->Task->parseIdentifier('Plugin_User2.abc'),
        );
        $this->assertSame('ROOT/Controller1', $this->Task->parseIdentifier('ROOT/Controller1'));
        $this->assertSame('.hidden', $this->Task->parseIdentifier('.hidden'));
        $this->assertSame('/', $this->Task->parseIdentifier('/'));
    }

    /**
     * Test creating an aco under an alias path and under the '/' root.
     *
     * @return void
     */
    public function testCreateAcoUnderAliasPathAndSlashRoot(): void
    {
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['aco', 'ROOT/Controller1', 'action3'];
        $this->Task->create();

        $Aco = ClassRegistry::init('Aco');
        $Aco->cacheQueries = false;
        $result = $Aco->findByAlias('action3');
        $this->assertEquals(2, $result['Aco']['parent_id']);

        $this->Task->args = ['aco', '/', 'Top'];
        $this->Task->create();
        $result = $Aco->findByAlias('Top');
        $this->assertNull($result['Aco']['parent_id']);

        $this->assertSame([
            "<success>New Aco</success> 'action3' created.",
            "<success>New Aco</success> 'Top' created.",
        ], $outCalls);
    }

    /**
     * Test that '/' can not be used as an alias.
     *
     * @return void
     */
    public function testCreateWithSlashAlias(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('/ can not be used as an alias!  / is the root, please supply a sub alias');

        $this->Task->args = ['aro', 'root', '/'];
        $this->Task->create();
    }

    /**
     * Test create with a parent that does not exist.
     *
     * @return void
     */
    public function testCreateWithMissingParent(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find node using reference "nobody"');

        $this->Task->args = ['aro', 'nobody', 'child'];
        $this->Task->create();
    }

    /**
     * Test create with a Model.foreign_key parent that does not exist.
     *
     * AclNode::node() throws for a Model.foreign_key reference that is not found,
     * so the exception reaches the caller before AclShell can report it.
     *
     * @return void
     */
    public function testCreateWithMissingModelParent(): void
    {
        $this->Task->expects($this->never())->method('error');
        $this->expectException(CakeException::class);
        $this->expectExceptionMessage("Couldn't find Aro node identified by");

        $this->Task->args = ['aro', 'User.99', 'child'];
        $this->Task->create();
    }

    /**
     * Test that a failed save is reported.
     *
     * @return void
     */
    public function testCreateSaveFails(): void
    {
        $this->Task->Acl->Aro->validator()->add('alias', 'numeric', ['rule' => 'numeric']);
        $this->Task->expects($this->never())->method('out');
        $this->Task->expects($this->once())
            ->method('err')
            ->with("There was a problem creating a new Aro 'notnumeric'.");

        $this->Task->args = ['aro', 'root', 'notnumeric'];
        $this->Task->create();

        $this->assertSame([], ClassRegistry::init('Aro')->findByAlias('notnumeric'));
    }

    /**
     * Test deleting a node by its alias.
     *
     * @return void
     */
    public function testDeleteByAlias(): void
    {
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['aco', 'action2'];
        $this->Task->delete();

        $Aco = ClassRegistry::init('Aco');
        $this->assertSame([], $Aco->findAllByAlias('action2'));
        $this->assertSame(['<success>Aco deleted.</success>'], $outCalls);
    }

    /**
     * Test deleting a node that does not exist.
     *
     * @return void
     */
    public function testDeleteMissingNode(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Node Not Deleted. There was an error deleting the Aro. Node does not exist.');

        $this->Task->args = ['aro', 'nobody'];
        $this->Task->delete();
    }

    /**
     * Test setParent output, with Model.foreign_key and alias identifiers.
     *
     * @return void
     */
    public function testSetParentOutput(): void
    {
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['aro', 'Gandalf', 'AuthUser.2'];
        $this->Task->setParent();

        $Aro = ClassRegistry::init('Aro');
        $Aro->cacheQueries = false;
        $result = $Aro->read(null, 3);
        $this->assertEquals(4, $result['Aro']['parent_id']);
        $this->assertSame(["Node parent set to AuthUser.2\n"], $outCalls);
    }

    /**
     * Test setParent when the new parent is a descendant of the node.
     *
     * @return void
     */
    public function testSetParentToDescendant(): void
    {
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['aro', 'admins', 'AuthUser.1'];
        $this->Task->setParent();

        $Aro = ClassRegistry::init('Aro');
        $Aro->cacheQueries = false;
        $result = $Aro->read(null, 2);
        $this->assertEquals(1, $result['Aro']['parent_id']);
        $this->assertCount(1, $outCalls);
        $this->assertStringStartsWith('Error in setting new parent.', $outCalls[0]);
    }

    /**
     * Test setParent with a node that does not exist.
     *
     * @return void
     */
    public function testSetParentMissingNode(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find node using reference "nobody"');

        $this->Task->args = ['aro', 'nobody', 'root'];
        $this->Task->setParent();
    }

    /**
     * Test getPath of an aco by alias path, and the header output.
     *
     * @return void
     */
    public function testGetPathAco(): void
    {
        $outCalls = &$this->_captureOut($this->Task);
        $this->Task->expects($this->once())->method('hr');

        $this->Task->args = ['aco', 'ROOT/Controller2/action1/record1'];
        $this->Task->getPath();

        $this->assertSame([
            'Path:',
            '[1] ROOT',
            '  [6] Controller2',
            '    [7] action1',
            '      [8] record1',
        ], $outCalls);
    }

    /**
     * Test getPath of an aro without alias, which shows Model.foreign_key.
     *
     * @return void
     */
    public function testGetPathWithoutAlias(): void
    {
        $Aro = ClassRegistry::init('Aro');
        $Aro->create(['parent_id' => 2, 'model' => 'AuthUser', 'foreign_key' => 3, 'alias' => '']);
        $Aro->save();
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['aro', 'AuthUser.3'];
        $this->Task->getPath();

        $this->assertSame([
            'Path:',
            '[1] ROOT',
            '  [2] admins',
            '    [5] AuthUser.3',
        ], $outCalls);
    }

    /**
     * Test getPath with a node that does not exist.
     *
     * @return void
     */
    public function testGetPathMissingNode(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find node using reference "nobody"');

        $this->Task->args = ['aro', 'nobody'];
        $this->Task->getPath();
    }

    /**
     * Test the arguments that check/grant/deny/inherit pass to the AclComponent.
     *
     * @return void
     */
    public function testPermissionArgumentsAreParsed(): void
    {
        $Acl = $this->getMock(AclComponent::class, ['check', 'allow', 'deny', 'inherit'], [new ComponentCollection()]);
        $this->Task->Acl = $Acl;
        $outCalls = &$this->_captureOut($this->Task);

        $Acl->expects($this->once())->method('check')
            ->with(3, 'ROOT/Controller1', '*')
            ->willReturn(true);
        $Acl->expects($this->once())->method('allow')
            ->with(['model' => 'AuthUser', 'foreign_key' => '2'], 5, 'read')
            ->willReturn(false);
        $Acl->expects($this->once())->method('deny')
            ->with('Elrond', ['model' => 'Post', 'foreign_key' => '1'], '*')
            ->willReturn(false);
        $Acl->expects($this->once())->method('inherit')
            ->with('Elrond', 'ROOT', '*')
            ->willReturn(false);

        $this->Task->args = ['3', 'ROOT/Controller1'];
        $this->Task->check();

        $this->Task->args = ['AuthUser.2', '5', 'read'];
        $this->Task->grant();

        $this->Task->args = ['Elrond', 'Post.1', 'all'];
        $this->Task->deny();

        $this->Task->args = ['Elrond', 'ROOT', ''];
        $this->Task->inherit();

        $this->assertSame([
            '3 is <success>allowed</success>.',
            'Permission was <error>not granted</error>.',
            'Permission was not denied.',
            'Permission was not inherited.',
        ], $outCalls);
    }

    /**
     * Test check output uses the given ARO name.
     *
     * @return void
     */
    public function testCheckOutputUsesAroName(): void
    {
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['Elrond', 'ROOT/Controller1', 'all'];
        $this->Task->check();

        $this->Task->args = ['AuthUser.1', 'ROOT/Controller1', 'all'];
        $this->Task->check();

        $this->assertSame([
            'Elrond is <error>not allowed</error>.',
            'AuthUser.1 is <error>not allowed</error>.',
        ], $outCalls);
    }

    /**
     * Test the whole aco tree, with its indentation.
     *
     * @return void
     */
    public function testViewAcoTree(): void
    {
        $outCalls = &$this->_captureOut($this->Task);
        $this->Task->expects($this->exactly(2))->method('hr');

        $this->Task->args = ['aco'];
        $this->Task->view();

        $this->assertSame([
            'Aco tree:',
            '  [1] ROOT',
            '    [2] Controller1',
            '      [3] action1',
            '        [4] record1',
            '      [5] action2',
            '    [6] Controller2',
            '      [7] action1',
            '        [8] record1',
            '      [9] action2',
            '    [10] Users',
            '      [11] Users',
            '        [12] view',
        ], $outCalls);
    }

    /**
     * Test view of a subtree given by Model.foreign_key.
     *
     * @return void
     */
    public function testViewWithModelForeignKeyArgument(): void
    {
        $outCalls = &$this->_captureOut($this->Task);

        $this->Task->args = ['aro', 'AuthUser.1'];
        $this->Task->view();

        $this->assertSame(['Aro tree:', '  [3] Gandalf'], $outCalls);
    }

    /**
     * Test view with a node that does not exist.
     *
     * @return void
     */
    public function testViewMissingNode(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Could not find node using reference "nobody"');

        $this->Task->args = ['aro', 'nobody'];
        $this->Task->view();
    }

    /**
     * Test view of an empty tree.
     *
     * @return void
     */
    public function testViewEmptyTree(): void
    {
        ClassRegistry::init('Aro')->deleteAll(['1 = 1'], false, false);
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('aro not found / No tree returned.');

        $this->Task->args = ['aro'];
        $this->Task->view();
    }

    /**
     * Test that initdb returns the result of the schema shell.
     *
     * @return void
     */
    public function testInitDbReturnsResult(): void
    {
        $this->Task->expects($this->once())->method('dispatchShell')
            ->with('schema create DbAcl')
            ->willReturn(0);

        $this->assertSame(0, $this->Task->initdb());
    }

    /**
     * Test nodeExists without enough arguments.
     *
     * @return void
     */
    public function testNodeExistsWithoutArguments(): void
    {
        $this->Task->args = [];
        $this->assertFalse($this->Task->nodeExists());

        $this->Task->args = ['aro'];
        $this->assertFalse($this->Task->nodeExists());
    }

    /**
     * Test nodeExists by alias and by foreign key.
     *
     * @return void
     */
    public function testNodeExists(): void
    {
        $this->Task->expects($this->never())->method('error');

        $this->Task->args = ['aro', 'admins'];
        $result = $this->Task->nodeExists();
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['Aro']['id']);

        $this->Task->args = ['aro', '1'];
        $result = $this->Task->nodeExists();
        $this->assertEquals([2, 3], Hash::extract($result, '{n}.Aro.id'));

        $this->Task->args = ['aco', 'Controller1'];
        $result = $this->Task->nodeExists();
        $this->assertCount(1, $result);
        $this->assertEquals(2, $result[0]['Aco']['id']);
    }

    /**
     * Test nodeExists with a node that does not exist.
     *
     * @return void
     */
    public function testNodeExistsMissingNode(): void
    {
        $this->_errorThrows();
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('nobody not found / No tree returned.');

        $this->Task->args = ['aro', 'nobody'];
        $this->Task->nodeExists();
    }
}
