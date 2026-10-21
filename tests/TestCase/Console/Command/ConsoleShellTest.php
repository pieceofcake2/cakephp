<?php
/**
 * ConsoleShellTest file
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
 * @package       Cake.Test.Case.Console.Command
 * @since         CakePHP(tm) v 2.13
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Test\TestCase\Console\Command;

use Cake\Console\Command\ConsoleShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOutput;
use Cake\Core\App;
use Cake\Core\Configure;
use Cake\Routing\Router;
use Cake\TestSuite\CakeTestCase;
use Cake\Utility\ClassRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use TestTwoPlugin\Model\NamespacedPluginItem;
use TestTwoPlugin\Model\NamespacedPluginPost;
use TestTwoPlugin\Model\TestTwoPluginAppModel;

/**
 * ConsoleShellTest
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ConsoleShellTest extends CakeTestCase
{
    /**
     * Fixtures
     *
     * @var array
     */
    public $fixtures = ['core.article', 'core.user', 'core.comment', 'core.tag', 'core.articles_tag'];

    /**
     * The shell under test
     *
     * @var ConsoleShell&MockObject
     */
    public $Shell;

    /**
     * The lines the shell printed with out()
     *
     * @var array
     */
    public $outputs = [];

    /**
     * The lines the shell printed with err()
     *
     * @var array
     */
    public $errors = [];

    /**
     * Whether a test changed the model path
     *
     * @var bool
     */
    protected $_modelsChanged = false;

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        Configure::write('App.namespace', 'TestApp');

        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            ConsoleShell::class,
            ['in', 'out', 'err', 'hr', '_stop'],
            [$out, $out, $in],
        );

        $this->outputs = [];
        $this->errors = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = null) {
                $this->outputs[] = $message;

                return 0;
            });
        $this->Shell->expects($this->any())
            ->method('err')
            ->willReturnCallback(function ($message = null) {
                $this->errors[] = $message;

                return 0;
            });
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Shell);
        Router::reload();

        parent::tearDown();

        if ($this->_modelsChanged) {
            // Rebuild the in-memory model list from the restored paths
            App::objects('Model', null, false);
            $this->_modelsChanged = false;
        }
    }

    /**
     * Gives the shell the models from the fixtures without running startup()
     *
     * @return void
     */
    protected function _loadModels(): void
    {
        $this->Shell->models = ['Article', 'Comment', 'Tag', 'User'];
        foreach ($this->Shell->models as $model) {
            $this->Shell->{$model} = ClassRegistry::init($model);
        }
    }

    /**
     * Makes the shell read the given lines, followed by 'quit'
     *
     * @param array $lines The lines to read.
     * @return void
     */
    protected function _input(array $lines): void
    {
        $lines[] = 'quit';
        $this->Shell->expects($this->exactly(count($lines)))
            ->method('in')
            ->with('')
            ->willReturnOnConsecutiveCalls(...$lines);
    }

    /**
     * Returns the printed lines as one string
     *
     * @return string
     */
    protected function _output(): string
    {
        $lines = [];
        foreach ($this->outputs as $output) {
            $lines[] = implode("\n", (array)$output);
        }

        return implode("\n", $lines);
    }

    /**
     * Test that startup() loads the models of the model path
     *
     * @return void
     */
    public function testStartup(): void
    {
        // A small model directory whose classes are all concrete
        $this->_modelsChanged = true;
        Configure::write('App.namespace', 'TestTwoPlugin');
        App::build([
            'Model' => [CORE_TESTS . DS . 'test_app' . DS . 'plugins' . DS . 'TestTwoPlugin' . DS . 'src' . DS . 'Model' . DS],
        ], App::RESET);
        $models = App::objects('Model', null, false);
        $this->assertSame(['NamespacedPluginItem', 'NamespacedPluginPost', 'TestTwoPluginAppModel'], $models);

        $this->Shell->expects($this->once())->method('hr');
        $this->Shell->startup();

        $this->assertSame($models, $this->Shell->models);
        $this->assertInstanceOf(NamespacedPluginItem::class, $this->Shell->NamespacedPluginItem);
        $this->assertInstanceOf(NamespacedPluginPost::class, $this->Shell->NamespacedPluginPost);
        $this->assertInstanceOf(TestTwoPluginAppModel::class, $this->Shell->TestTwoPluginAppModel);
        $expected = [
            'Model classes:',
            ' - NamespacedPluginItem',
            ' - NamespacedPluginPost',
            ' - TestTwoPluginAppModel',
        ];
        $this->assertSame($expected, $this->outputs);
        $this->assertSame([], $this->errors);
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Shell->getOptionParser();

        $this->assertStringContainsString('interactive console', $parser->description());
        $this->assertStringContainsString('ModelA bind <association> ModelB', $parser->epilog());
        $this->assertStringContainsString('Routes show', $parser->epilog());
    }

    /**
     * Test the help command and its alias
     *
     * @return void
     */
    public function testHelp(): void
    {
        $this->_input(['help', '?']);
        $this->Shell->main();

        $this->assertCount(2, $this->outputs);
        $epilog = $this->Shell->getOptionParser()->epilog();
        $this->assertSame([$epilog, $epilog], $this->outputs);
    }

    /**
     * Test that main() runs the command it is given before it reads more
     *
     * @return void
     */
    public function testMainWithCommand(): void
    {
        $this->_loadModels();
        $this->_input([]);
        $this->Shell->main('models');

        $this->assertSame('Model classes:', $this->outputs[0]);
    }

    /**
     * Test that 'exit' ends the loop
     *
     * @return void
     */
    public function testExit(): void
    {
        $this->Shell->expects($this->once())->method('in')->willReturn('exit');
        $this->Shell->main();

        $this->assertSame([], $this->outputs);
    }

    /**
     * Test an invalid command
     *
     * @return void
     */
    public function testInvalidCommand(): void
    {
        $this->_input(['not a command']);
        $this->Shell->main();

        $this->assertSame(['Invalid command', null], $this->outputs);
    }

    /**
     * Test the models command
     *
     * @return void
     */
    public function testModels(): void
    {
        $this->_loadModels();
        $this->Shell->expects($this->once())->method('hr');
        $this->_input(['models']);
        $this->Shell->main();

        $expected = ['Model classes:', ' - Article', ' - Comment', ' - Tag', ' - User'];
        $this->assertSame($expected, $this->outputs);
    }

    /**
     * Test the bind command
     *
     * @return void
     */
    public function testBind(): void
    {
        $this->_loadModels();
        $this->_input([
            'User bind hasMany Comment',
            'User bind hasMany Missing',
            'User bind notAnAssociation Comment',
        ]);
        $this->Shell->main();

        $expected = [
            'Created hasMany association between User and Comment',
            'Please verify you are using valid models and association types',
            'Please verify you are using valid models and association types',
        ];
        $this->assertSame($expected, $this->outputs);
        $this->assertArrayHasKey('Comment', $this->Shell->User->hasMany);
    }

    /**
     * Test the unbind command
     *
     * @return void
     */
    public function testUnbind(): void
    {
        $this->_loadModels();
        $this->_input([
            'Article unbind hasMany Comment',
            'Article unbind hasMany Comment',
            'Article unbind belongsTo Missing',
            'Article unbind notAnAssociation User',
            'Missing unbind hasMany Comment',
        ]);
        $this->Shell->main();

        $invalid = 'Please verify you are using valid models, valid current association, and valid association types';
        $expected = [
            'Removed hasMany association between Article and Comment',
            $invalid,
            $invalid,
            $invalid,
            $invalid,
        ];
        $this->assertSame($expected, $this->outputs);
        $this->assertArrayNotHasKey('Comment', $this->Shell->Article->getAssociated());
        $this->assertArrayHasKey('User', $this->Shell->Article->getAssociated());
    }

    /**
     * Test the find command with find('all') results
     *
     * @return void
     */
    public function testFindAll(): void
    {
        $this->_loadModels();
        $this->Shell->Article->unbindModel(['hasAndBelongsToMany' => ['Tag']], false);
        $this->_input([
            "Article->find('all', array('conditions' => array('Article.id' => 1), 'recursive' => 1))",
        ]);
        $this->Shell->main();

        $output = $this->_output();
        $this->assertSame('Article', $this->outputs[0]);
        $this->assertStringContainsString("\tid: 1", $output);
        $this->assertStringContainsString("\ttitle: First Article", $output);
        $this->assertStringContainsString("User\n\tid: 1\n\tuser: mariano", $output);
        $this->assertStringContainsString("Comment\n\tid: 1\n\tarticle_id: 1", $output);
        $this->assertStringContainsString("\tcomment: First Comment for First Article", $output);
    }

    /**
     * Test the find command with results that are keyed by name
     *
     * @return void
     */
    public function testFindKeyed(): void
    {
        $this->_loadModels();
        $this->_input([
            "Article->find('first', array('conditions' => array('Article.id' => 2), 'recursive' => -1))",
            "Article->find('neighbors', array('field' => 'id', 'value' => 2, 'recursive' => -1))",
        ]);
        $this->Shell->main();

        $output = $this->_output();
        $this->assertStringContainsString("Article\n\tid: 2\n\tuser_id: 3\n\ttitle: Second Article", $output);
        $this->assertStringContainsString("prev\n\tid: 1\n\tuser_id: 1\n\ttitle: First Article", $output);
        $this->assertStringContainsString("next\n\tid: 3\n\tuser_id: 1\n\ttitle: Third Article", $output);
    }

    /**
     * Test the find command without a result set, and with an invalid model
     *
     * @return void
     */
    public function testFindNoResultAndInvalidModel(): void
    {
        $this->_loadModels();
        $this->_input([
            "Article->find('count')",
            "Missing->find('all')",
        ]);
        $this->Shell->main();

        $this->assertSame([null, 'No result set found', 'Missing is not a valid model'], $this->outputs);
    }

    /**
     * Test that the find command strips the characters that are not allowed
     *
     * @return void
     */
    public function testFindStripsBadChars(): void
    {
        $this->_loadModels();
        $this->_input(["\$Article->find('count');"]);
        $this->Shell->main();

        $this->assertSame([null, 'No result set found'], $this->outputs);
    }

    /**
     * Test the save command
     *
     * @return void
     */
    public function testSave(): void
    {
        $this->_loadModels();
        $before = $this->Shell->Article->find('count');
        $this->_input([
            "Article->save(array('user_id' => 1, 'title' => 'Console', 'body' => 'Saved from the console'))",
            "Missing->save(array('title' => 'Nothing'))",
        ]);
        $this->Shell->main();

        $this->assertSame(['Saved record for Article'], $this->outputs);
        $this->assertSame($before + 1, $this->Shell->Article->find('count'));
        $saved = $this->Shell->Article->find('first', [
            'conditions' => ['Article.title' => 'Console'],
            'recursive' => -1,
        ]);
        $this->assertSame('Saved from the console', $saved['Article']['body']);
    }

    /**
     * Test the columns command
     *
     * @return void
     */
    public function testColumns(): void
    {
        $this->_loadModels();
        $this->_input(['User columns', 'Missing columns']);
        $this->Shell->main();

        $expected = [
            "\tid: integer",
            "\tuser: string",
            "\tpassword: string",
            "\tcreated: datetime",
            "\tupdated: datetime",
            'Please verify that you selected a valid model',
        ];
        $this->assertSame($expected, $this->outputs);
    }

    /**
     * Test the routes reload command
     *
     * @return void
     */
    public function testRoutesReload(): void
    {
        $this->_input(['routes reload']);
        $this->Shell->main();

        $this->assertNotEmpty(Router::$routes);
        $this->assertSame(
            [sprintf('Routes configuration reloaded, %d routes connected', count(Router::$routes))],
            $this->outputs,
        );
        $this->assertSame([], $this->errors);
    }

    /**
     * Test the routes show command
     *
     * @return void
     */
    public function testRoutesShow(): void
    {
        Router::reload();
        Router::connect('/console_articles/*', ['controller' => 'articles', 'action' => 'index']);
        $this->_input(['Routes Show']);
        $this->Shell->main();

        $this->assertCount(1, $this->outputs);
        $this->assertStringContainsString('[/console_articles/*] => Array', $this->outputs[0]);
        $this->assertStringContainsString('[controller] => articles', $this->outputs[0]);
        $this->assertStringContainsString('[action] => index', $this->outputs[0]);
        $this->assertSame([], $this->errors);
    }

    /**
     * Test the route command with a string URL
     *
     * @return void
     */
    public function testRouteToArray(): void
    {
        Router::reload();
        Router::connect('/console_articles/:action/*', ['controller' => 'articles']);
        $this->_input(['route /console_articles/view/1']);
        $this->Shell->main();

        $this->assertCount(1, $this->outputs);
        $this->assertStringContainsString("'controller' => 'articles'", $this->outputs[0]);
        $this->assertStringContainsString("'action' => 'view'", $this->outputs[0]);
        $this->assertStringContainsString("0 => '1'", $this->outputs[0]);
    }

    /**
     * Test the route command with an array URL
     *
     * @return void
     */
    public function testRouteToString(): void
    {
        Router::reload();
        Router::connect('/console_articles/:action/*', ['controller' => 'articles']);
        $this->_input([
            "route ('controller' => 'articles', 'action' => 'view', 1)",
            'route ()',
        ]);
        $this->Shell->main();

        $this->assertSame(['/console_articles/view/1'], $this->outputs);
    }
}
