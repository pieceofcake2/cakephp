<?php
/**
 * ApiShellTest file
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

use Cake\Console\Command\ApiShell;
use Cake\Console\ConsoleInput;
use Cake\Console\ConsoleOptionParser;
use Cake\Console\ConsoleOutput;
use Cake\TestSuite\CakeTestCase;
use ReflectionMethod;
use RuntimeException;

/**
 * ApiShellTest class
 *
 * @package       Cake.Test.Case.Console.Command
 */
class ApiShellTest extends CakeTestCase
{
    public ?ApiShell $Shell = null;

    /**
     * The messages passed to out()
     *
     * @var array
     */
    public array $outCalls = [];

    /**
     * setUp method
     *
     * @return void
     */
    public function setUp(): void
    {
        parent::setUp();
        $out = $this->getMock(ConsoleOutput::class, [], [], '', false);
        $in = $this->getMock(ConsoleInput::class, [], [], '', false);

        $this->Shell = $this->getMock(
            ApiShell::class,
            ['in', 'out', 'err', 'createFile', 'hr', '_stop'],
            [$out, $out, $in],
        );
    }

    /**
     * tearDown method
     *
     * @return void
     */
    public function tearDown(): void
    {
        unset($this->Shell);

        parent::tearDown();
    }

    /**
     * Collects the messages passed to out() in $outCalls.
     *
     * @return void
     */
    protected function _captureOut(): void
    {
        $this->outCalls = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') {
                $this->outCalls[] = $message;

                return 0;
            });
    }

    /**
     * Test that method names are detected properly including those with no arguments.
     *
     * @return void
     */
    public function testMethodNameDetection(): void
    {
        $this->Shell->expects($this->any())->method('in')->will($this->returnValue('q'));
        $outCalls = [];
        $this->Shell->expects($this->any())
            ->method('out')
            ->willReturnCallback(function ($message = '') use (&$outCalls) {
                $outCalls[] = $message;
            });

        $expected = [
            '1. afterFilter()',
            '2. afterScaffoldSave($method)',
            '3. afterScaffoldSaveError($method)',
            '4. beforeFilter()',
            '5. beforeRedirect($url, $status = NULL, $exit = true)',
            '6. beforeRender()',
            '7. beforeScaffold($method)',
            '8. constructClasses()',
            '9. disableCache()',
            '10. flash($message, $url, $pause = 1, $layout = \'flash\')',
            '11. getEventManager()',
            '12. header($status)',
            '13. httpCodes($code = NULL)',
            '14. implementedEvents()',
            '15. invokeAction($request)',
            '16. loadModel($modelClass = NULL, $id = NULL)',
            '17. paginate($object = NULL, $scope = array (), $whitelist = array ())',
            '18. postConditions($data = array (), $op = NULL, $bool = \'AND\', $exclusive = false)',
            '19. redirect($url, $status = NULL, $exit = true)',
            '20. referer($default = NULL, $local = false)',
            '21. render($view = NULL, $layout = NULL)',
            '22. scaffoldError($method)',
            '23. set($one, $two = NULL)',
            '24. setAction($action, ...$args)',
            '25. setRequest($request)',
            '26. shutdownProcess()',
            '27. startupProcess()',
            '28. validate(...$args)',
            '29. validateErrors(...$objects)',
        ];

        $this->Shell->args = ['controller'];
        $this->Shell->paths['controller'] = CAKE . 'Controller' . DS;
        $this->Shell->main();

        $this->assertEquals('Controller', $outCalls[0]);
        $this->assertEquals($expected, $outCalls[1]);
    }

    /**
     * Test that initialize() sets the paths for every type
     *
     * @return void
     */
    public function testInitialize(): void
    {
        $this->Shell->paths = ['custom' => '/custom/'];
        $this->Shell->initialize();

        $this->assertSame([
            'custom' => '/custom/',
            'behavior' => CAKE . 'Model' . DS . 'Behavior' . DS,
            'cache' => CAKE . 'Cache' . DS,
            'controller' => CAKE . 'Controller' . DS,
            'component' => CAKE . 'Controller' . DS . 'Component' . DS,
            'helper' => CAKE . 'View' . DS . 'Helper' . DS,
            'model' => CAKE . 'Model' . DS,
            'view' => CAKE . 'View' . DS,
            'core' => CAKE,
        ], $this->Shell->paths);
    }

    /**
     * Data provider for testClassLookup
     *
     * @return array
     */
    public static function classLookupProvider(): array
    {
        return [
            'behavior' => [['behavior', 'TranslateBehavior'], 'TranslateBehavior', 'bindTranslation('],
            'cache' => [['cache', 'Cache'], 'Cache', 'config('],
            'cache engine' => [['cache', 'CacheEngine'], 'CacheEngine', 'init('],
            'controller' => [['controller', 'Controller'], 'Controller', 'render('],
            'component' => [['component', 'AuthComponent'], 'AuthComponent', 'isAuthorized('],
            'helper' => [['helper', 'HtmlHelper'], 'HtmlHelper', 'link('],
            'underscored helper' => [['helper', 'html_helper'], 'HtmlHelper', 'link('],
            'model' => [['model', 'Model'], 'Model', 'save('],
            'view' => [['view', 'View'], 'View', 'render('],
            'type as class' => [['Model'], 'Model', 'save('],
        ];
    }

    /**
     * Test the class lookup for each type
     *
     * @dataProvider classLookupProvider
     * @param array $args The shell arguments.
     * @param string $title The expected class name.
     * @param string $method The start of a method that is expected in the list.
     * @return void
     */
    public function testClassLookup(array $args, string $title, string $method): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('q'));
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->expects($this->never())->method('err');
        $this->_captureOut();

        $this->Shell->args = $args;
        $this->assertNull($this->Shell->main());

        $this->assertSame($title, $this->outCalls[0]);
        $this->assertIsArray($this->outCalls[1]);
        $this->assertNotEmpty(preg_grep('/^\d+\. ' . preg_quote($method, '/') . '/', $this->outCalls[1]));
        $this->assertSame('Done', $this->outCalls[2]);
    }

    /**
     * Test that a class name for a behavior, component or helper type gets the type suffix
     * when the type path has a class of that name without the suffix
     *
     * @return void
     */
    public function testClassLookupAddsTypeSuffix(): void
    {
        $this->Shell->initialize();
        // Cache.php is in the cache path, and CacheHelper is the class that gets looked up.
        $this->Shell->paths['helper'] = CAKE . 'Cache' . DS;
        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('q'));
        $this->_captureOut();

        $this->Shell->args = ['helper', 'Cache'];
        $this->Shell->main();

        $this->assertSame('CacheHelper', $this->outCalls[0]);
        $this->assertNotEmpty(preg_grep('/^\d+\. afterRenderFile\(\$viewFile, \$output\)$/', $this->outCalls[1]));
    }

    /**
     * Test that the classes of each path are listed again for every lookup
     *
     * @return void
     */
    public function testClassLookupOfTwoTypes(): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->exactly(2))->method('in')->will($this->returnValue('q'));
        $this->Shell->expects($this->never())->method('err');
        $this->_captureOut();

        $this->Shell->args = ['model', 'Model'];
        $this->Shell->main();
        $this->Shell->args = ['helper', 'HtmlHelper'];
        $this->Shell->main();

        $this->assertSame('Model', $this->outCalls[0]);
        $this->assertNotEmpty(preg_grep('/^\d+\. save\(/', $this->outCalls[1]));
        $this->assertSame('HtmlHelper', $this->outCalls[3]);
        $this->assertNotEmpty(preg_grep('/^\d+\. link\(/', $this->outCalls[4]));
    }

    /**
     * Test that an unknown class is reported as an error
     *
     * @return void
     */
    public function testUnknownClass(): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->once())
            ->method('err')
            ->with('<error>Error:</error> NotAClass not found');
        $this->Shell->expects($this->once())
            ->method('_stop')
            ->will($this->throwException(new RuntimeException('stopped')));

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('stopped');

        $this->Shell->args = ['helper', 'NotAClass'];
        $this->Shell->main();
    }

    /**
     * Test that an unknown type looks for the class in the core path
     *
     * @return void
     */
    public function testUnknownTypeUsesCorePath(): void
    {
        $this->Shell->initialize();
        // No class is directly in CAKE, so the core path points at the helpers.
        $this->Shell->paths['core'] = CAKE . 'View' . DS . 'Helper' . DS;
        $this->Shell->expects($this->once())->method('in')->will($this->returnValue('q'));
        $this->Shell->expects($this->never())->method('err');
        $this->_captureOut();

        $this->Shell->args = ['unknown', 'HtmlHelper'];
        $this->Shell->main();

        $this->assertSame('HtmlHelper', $this->outCalls[0]);
        $this->assertNotEmpty(preg_grep('/^\d+\. link\(/', $this->outCalls[1]));
    }

    /**
     * Test that a class file that cannot be included is reported, and gives no methods
     *
     * @return void
     */
    public function testParseClassFileNotIncluded(): void
    {
        $path = TMP . 'api_shell_missing' . DS . 'ApiShellMissingClass.php';
        $this->Shell->expects($this->once())
            ->method('err')
            ->with($path . ' could not be found');

        $method = new ReflectionMethod($this->Shell, '_parseClass');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }
        $warnings = [];
        set_error_handler(function ($errno, $errstr) use (&$warnings) {
            $warnings[] = $errstr;

            return true;
        }, E_WARNING);
        try {
            $result = $method->invoke($this->Shell, $path, 'ApiShellMissingClass');
        } finally {
            restore_error_handler();
        }

        $this->assertSame([], $result);
        $this->assertNotEmpty($warnings);
    }

    /**
     * Test the details of one method with the method option
     *
     * @return void
     */
    public function testMethodOption(): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->never())->method('in');
        $this->Shell->expects($this->once())->method('hr');
        $this->_captureOut();

        $this->Shell->args = ['helper', 'HtmlHelper'];
        $this->Shell->params['method'] = 'link';
        $this->assertNull($this->Shell->main());

        $this->assertSame('HtmlHelper::link($title, $url = NULL, $options = array (), $confirmMessage = false)', $this->outCalls[0]);
        $this->assertStringContainsString('Creates an HTML link.', $this->outCalls[1]);
    }

    /**
     * Test that an unknown method is reported
     *
     * @return void
     */
    public function testMethodOptionUnknownMethod(): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->once())
            ->method('err')
            ->with('HtmlHelper::noSuchMethod() could not be found');
        $this->Shell->expects($this->once())->method('_stop');
        $this->Shell->expects($this->never())->method('out');

        $this->Shell->args = ['helper', 'HtmlHelper'];
        $this->Shell->params['method'] = 'noSuchMethod';
        $this->assertNull($this->Shell->main());
    }

    /**
     * Test selecting methods from the interactive list
     *
     * @return void
     */
    public function testInteractiveMethodDetails(): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->exactly(3))
            ->method('in')
            ->will($this->onConsecutiveCalls('1', '999', 'q'));
        $this->_captureOut();

        $this->Shell->args = ['view', 'View'];
        $this->Shell->main();

        $list = $this->outCalls[1];
        $this->assertSame('1. addScript($name, $content = NULL)', $list[0]);

        $this->assertSame('View::addScript($name, $content = NULL)', $this->outCalls[2]);
        $this->assertStringContainsString('Adds a script block or other element to be inserted', $this->outCalls[3]);
        $this->assertStringContainsString('@see View::start()', $this->outCalls[3]);
        $this->assertSame('Done', $this->outCalls[4]);
        $this->assertCount(5, $this->outCalls);
    }

    /**
     * Test that 'l' lists the methods again, and other input that is not a number is ignored
     *
     * @return void
     */
    public function testInteractiveListAgain(): void
    {
        $this->Shell->initialize();
        $this->Shell->expects($this->exactly(3))
            ->method('in')
            ->will($this->onConsecutiveCalls('l', 'x', 'q'));
        $this->Shell->expects($this->once())->method('hr');
        $this->_captureOut();

        $this->Shell->args = ['view', 'View'];
        $this->Shell->main();

        $this->assertCount(4, $this->outCalls);
        $this->assertSame('View', $this->outCalls[0]);
        $this->assertIsArray($this->outCalls[1]);
        $this->assertSame($this->outCalls[1], $this->outCalls[2]);
        $this->assertSame('Done', $this->outCalls[3]);
    }

    /**
     * Test that main() without arguments shows the help of the option parser
     *
     * @return void
     */
    public function testMainWithoutArguments(): void
    {
        $this->Shell->OptionParser = $this->getMock(ConsoleOptionParser::class, [], [null, false]);
        $this->Shell->OptionParser->expects($this->once())
            ->method('help')
            ->will($this->returnValue('the help'));
        $this->Shell->expects($this->once())
            ->method('out')
            ->with('the help')
            ->will($this->returnValue(8));

        $this->Shell->args = [];
        $this->assertSame(8, $this->Shell->main());
    }

    /**
     * Test the option parser
     *
     * @return void
     */
    public function testGetOptionParser(): void
    {
        $parser = $this->Shell->getOptionParser();

        $arguments = $parser->arguments();
        $this->assertSame('type', $arguments[0]->name());
        $this->assertSame('className', $arguments[1]->name());

        $options = $parser->options();
        $this->assertArrayHasKey('method', $options);
        $this->assertSame('m', $options['method']->short());
        $this->assertStringContainsString('Lookup doc block comments for classes in CakePHP.', $parser->help());
    }

    /**
     * Test the help of all parameters
     *
     * @return void
     */
    public function testHelp(): void
    {
        $this->_captureOut();

        $this->Shell->args = [];
        $this->Shell->help();

        $this->assertCount(3, $this->outCalls);
        $this->assertStringStartsWith('Usage: cake api [<type>] <className> [-m <method>]', $this->outCalls[0]);
        $this->assertStringContainsString("\t\tbehavior\tLook for class in CakePHP behavior path", $this->outCalls[1]);
        $this->assertStringContainsString("\t<className>\n", $this->outCalls[2]);
    }

    /**
     * Data provider for testHelpOneParameter
     *
     * @return array
     */
    public static function helpOneParameterProvider(): array
    {
        return [
            'path' => ['Path', "\t<type>\n", "\t\tview\tLook for class in CakePHP view path\n\n\n"],
            'type' => ['type', "\t<type>\n", "\t\tview\tLook for class in CakePHP view path\n\n\n"],
            'className' => ['className', "\t<className>\n", "(e.g: Component, HtmlHelper).\n\n\n"],
            'classname' => ['classname', "\t<className>\n", "(e.g: Component, HtmlHelper).\n\n\n"],
        ];
    }

    /**
     * Test the help of one parameter
     *
     * @dataProvider helpOneParameterProvider
     * @param string $parameter The parameter name given to help.
     * @param string $start The expected start of the help.
     * @param string $end The expected end of the help.
     * @return void
     */
    public function testHelpOneParameter(string $parameter, string $start, string $end): void
    {
        $this->_captureOut();

        $this->Shell->args = ['help', $parameter];
        $this->Shell->help();

        $this->assertCount(2, $this->outCalls);
        $this->assertStringStartsWith($start, $this->outCalls[1]);
        $this->assertStringEndsWith($end, $this->outCalls[1]);
    }

    /**
     * Test the help of an unknown parameter
     *
     * @return void
     */
    public function testHelpUnknownParameter(): void
    {
        $this->_captureOut();

        $this->Shell->args = ['help', 'nothing'];
        $this->Shell->help();

        $this->assertCount(2, $this->outCalls);
        $this->assertSame('Command nothing not found', $this->outCalls[1]);
    }
}
